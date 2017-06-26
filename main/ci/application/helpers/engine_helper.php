<?php

    function _anonymiseTrade($trade) {
        return array(
            'amount'    => $trade->amount,
            'datetime'  => $trade->_created,
            'date'      => date('m/d/Y', $trade->_created / 1000),
            'rate'      => $trade->rate,
            'value'     => $trade->value
        );
    }

    function _anonymiseTrade2($trade) {
        return array(
            'amount' => $trade->amount,
            'date'   => date('U', $trade->_created / 1000),
            'price'  => $trade->rate,
            'tid'    => _numeric($trade->_id)
        );
    }

    function _anonymiseTradeGUI($trade) {
        return array(
            'amount'     => $trade->amount,
            'datetime'   => $trade->_created,
            'date'       => date('m/d/Y H:i:s', $trade->_created / 1000),
            'rate'       => $trade->rate,
            'value'      => $trade->value,
            'buyerfirst' => (json_decode($trade->minor_order)->_created < json_decode($trade->major_order)->_created)
        );
    }

    function _anonymiseOrder($order) {
        return array(
            'amount' => $order->amount,
            'rate'   => $order->rate,
            'value'  => $order->value
        );
    }

    function _anonymiseOrderGUI($order) {
        return array(
            'amount' => $order->amount,
            'rate'   => $order->rate,
            'sum'    => $order->sum,
            'value'  => $order->value
        );
    }

    function _anonymiseOrder2($order) {
        return array($order->rate,$order->amount);
    }

    function _anonymiseOrderGSR($order) {
        return array(
            'amount' => $order->amount,
            'isgsr'  => $order->client == 1649,
            'rate'   => $order->rate,
            'value'  => $order->value
        );
    }

    function _anonymiseUserOrder($order) {
        return array(
            'amount'   => $order->amount,
            'book'     => $order->book,
            'datetime' => date("Y-m-d H:i:s", $order->_created / 1000),
            'id'       => $order->uid,
            'rate'     => $order->rate,
            'type'     => $order->type,
            'value'    => $order->value
        );
    }

    function _anonymiseUserOrderForAPI($order) {
        return array(
            'amount' => $order->amount,
            'book'   => $order->book,
            'id'     => $order->uid,
            'ip'     => $order->ip,
            'method' => $order->method,
            'rate'   => $order->rate,
            'type'   => $order->type,
            'value'  => $order->value
        );
    }

    function _anonymiseUserOrderForAPI2($order) {
        $status = 0;

        if (isset($order->status)) {
            switch ($order->status) {
                case 'completed':
                    $status = 2;
                    break;

                case 'cancelled':
                    $status = -1;
                    break;

                default:
                    $status = -99; // unknown
            }
        } else if ($order->_created != $order->_updated)
            $status = 1;

        return array(
            'amount'   => $order->amount,
            'book'     => $order->book,
            'datetime' => date("Y-m-d H:i:s", $order->_created / 1000),
            'id'       => $order->uid,
            'price'    => $order->rate,
            'status'   => (string)$status,
            'type'     => $order->type == 'buy' ? '0' : '1'
        );
    }

    function _anonymiseUserTradeForAPI($trade) {
        $tradeBook = $trade->major_currency . '_' . $trade->minor_currency;

        return array(
            $trade->minor_currency => (($trade->type=='buy') ? '-' . $trade->value : $trade->minor_total ),
            $trade->major_currency => (($trade->type=='sell') ? '-' . $trade->amount : $trade->major_total ),
            $tradeBook             => $trade->rate,
            'datetime'             => date("Y-m-d H:i:s", $trade->_created / 1000),
            'fee'                  => $trade->fee,
            'id'                   => $trade->id,
            'minor_currency'       => $trade->minor_currency,
            'major_currency'       => $trade->major_currency,
            'order_id'             => $trade->order_id,
            'rate'                 => $trade->rate,
            'type'                 => 2
        );
    }

    function _anonymiseUserTrades($trades, $userId) {
        $_trades = array();

        foreach ($trades as $trade) {
            $_trade = array(
                'amount'    => $trade->amount,
                'direction' => $trade->major_client == $userId ? 'sell' : 'buy',
                'rate'      => $trade->rate,
                'value'     => $trade->value
            );

            $_trades[] = $_trade;
        }

        return $_trades;
    }

    function _availableBalances($balances) {
        $_balances = array();

        foreach ((array)$balances as $key=>$balance) {
            if (strpos($key, '_available') !== false) {
                $currency = str_replace('_available', '', $key);
                $_balances[$currency] = $balance;
            }
        }

        return $_balances;
    }
    
    function _fullBalances($balances) {
        $_balances = array();

        foreach ((array)$balances as $key=>$balance) {
            if (strpos($key, '_full') !== false) {
                $currency = str_replace('_full', '', $key);
                $_balances[$currency] = $balance;
            }
        }

        return $_balances;
    }
    
    function _lockedBalances($balances) {
        $_balances = array();

        foreach ((array)$balances as $key=>$balance) {
            if (strpos($key, '_locked') !== false) {
                $currency = str_replace('_locked', '', $key);
                $_balances[$currency] = $balance;
            }
        }

        return $_balances;
    }

    function _numeric($string) {
        return (int)preg_replace('/[^\d]+/', '', $string);
    }