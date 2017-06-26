<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Notification_model extends Redis_model {
    private $_private   = array();
    private $_trades    = array();
    private $_broadcast = array();

    public function direct($event, $target, $data) {
        $this->_private[$event][$target] = $data;
    }

    public function addTrade($direction, $userId, $data) {
        $this->_trades[$direction][$userId][] = $data;

        $package = array(
            'book' => $data['book']
        );

        $this->direct('user_update', $userId, $package);
    }

    private function _processTradeNotifications() {
        foreach ($this->_trades as $type=>$users) {
            foreach ($users as $userId=>$trades) {
                $data = array(
                    'major'  => '',
                    'minor'  => '',
                    'book'   => '',
                    'amount' => '0',
                    'value'  => '0',
                    'fee'    => '0',
                    'count'  => 0
                );

                foreach ($trades as $trade) {
                    $data['major'] = $trade['major'];
                    $data['minor'] = $trade['minor'];
                    $data['book']  = $trade['book'];

                    $data['amount'] = bcadd($data['amount'], $trade['amount'], getPrecision($data['major']));
                    $data['value']  = bcadd($data['value'], $trade['value'], 2);
                    $data['fee']    = $type == 'buy' ? bcadd($data['fee'], $trade['majorFee'], getPrecision($data['major'])) : bcadd($data['fee'], $trade['minorFee'], getPrecision($data['minor']));
                    $data['count']++;
                }

                $hasFee = bccomp($data['fee'], '0', $type == 'buy' ? getPrecision($data['major']) : getPrecision($data['minor']));
                if ($type == 'buy')
                    $string = $hasFee ? 'm_trade_buy_fee' : 'm_trade_buy';
                else $string = $hasFee ? 'm_trade_sell_fee' : 'm_trade_sell';

                $message = array(
                    'user_id' => $userId,
                    'type'    => 'success',
                    'book'    => $data['book'],
                    'message' => _l($string, displayCurrency($data['major'], $data['amount']), displayCurrency($data['minor'], $data['value']), $hasFee ? displayCurrency($type == 'buy' ? $data['major'] : $data['minor'], $data['fee']) : '')
                );

                $this->direct('notification', $userId, $message);
            }
        }
    }

    public function broadcast($event, $data) {
        $this->_broadcast[$event] = $data;
    }

    public function flush() {
        $this->_processTradeNotifications();

        foreach ($this->_private as $event=>$users) {
            foreach ($users as $target=>$data) {
                $package = array(
                    'event'   => $event,
                    'target'  => $target,
                    'payload' => $data
                );

                $this->redis->publish('taurus.emit', json_encode($package));
            }
        }

        foreach ($this->_broadcast as $event=>$data) {
            $package = array(
                'event'   => $event,
                'payload' => $data
            );

            $this->redis->publish('taurus.broadcast', json_encode($package));
        }
    }

    public function pushOrderNotification($userId)
    {
        $package = array(
            'payload' => array("userId" => $userId)
        );

        $this->redis->publish('order_notification.update_user', json_encode($package) );

        $packagePublic = array(
            'payload' => array()
        );

        $this->redis->publish('order_notification.update_public', json_encode($packagePublic) );
    }
    public function pushTradeNotification($tradeId)
    {

        $packagePublic = array(
            'payload' => array('tradeId'=>$tradeId)
        );

        $this->redis->publish('trade_notification.update_trades', json_encode($packagePublic) );
    }
    public function pushUserNotification($userId)
    {

        $packagePublic = array(
            'payload' => array('userId'=>$userId)
        );

        $this->redis->publish('order_notification.update_user', json_encode($packagePublic) );
    }
    public function pushCancelOrderNotification($userId,$orderId)
    {

        $packagePublic = array(
            'payload' => array('userId'=>$userId,'orderId'=>$orderId,'operation'=>'cancel')
        );

        $this->redis->publish('order_notification.update_order', json_encode($packagePublic) );
        $packagePublic = array(
            'payload' => array('userId'=>$userId)
        );

        $this->redis->publish('order_notification.update_user', json_encode($packagePublic) );
    }
}