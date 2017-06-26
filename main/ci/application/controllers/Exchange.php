<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Exchange extends MY_Controller {

    public function __construct() {
        parent::__construct();
        show_404();
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error', 'You need to login before exchanging currencies');
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }

        if ($this->user->verified == 0) {
            $this->session->set_flashdata('error', 'You need to have your account verified in order to exchange currencies');
            redirect($this->config->item('default_redirect'));
        }

        $this->load->model('exchange_model');
    }

	public function index($from) {
        $currencies = $this->meta_model->getFiatCurrencies();

        // quick hack to remove the Oz of Gold from the dropdown
        foreach ($currencies as $idx=>$c) {
            if ($c == 'xau') {
                unset($currencies[$idx]);
                break;
            }
        }

        foreach ($currencies as $idx=>$c) {
            if ($c == $from) {
                unset($currencies[$idx]);
                break;
            }
        }

        reset($currencies);

        $currency = $this->input->post('currency');
        if (!$currency)
            $currency = current($currencies);

        $rate = $this->exchange_model->getRate($from, $currency);
        // Apply the fee
        $fee  = bcmul($rate, '0.04', 6);
        $rate = bcsub($rate, $fee, 6);

        $balances = $this->user_balance_model->get($this->userId);

        if ($this->input->post('preview')) {
            $this->form_validation->set_rules('amount', _l('l_amount'), 'required|valid_currency_format[' . $from . ']|greater_than[9.99]');

            if ($this->form_validation->run()) {
                $amount = $this->input->post('amount');

                if (bccomp($amount, $balances->{$from . '_available'}, 2) > 0) {
                    $this->form_validation->setError('amount', _l('e_insufficient_balance'));
                }
                else {
                    $alreadyExchanged = $this->exchange_model->findTotalExchanges($this->userId);

                    $newAmount = bcadd($alreadyExchanged, $amount, 2);

                    if (bccomp($newAmount, '999', 2) > 0) {
                        $this->form_validation->setError('amount', _l('e_over_daily_limit'));
                    }
                    else {
                        $value = bcmul($amount, $rate, 2);

                        $data = array(
                            'from'   => $from,
                            'to'     => $currency,
                            'amount' => $amount,
                            'rate'   => $rate,
                            'value'  => $value,
                            'client' => $this->userId
                        );

                        $code = $this->exchange_model->saveTemp($data);

                        $data['code'] = $code;

                        $this->session->set_flashdata('exchange_code', $code);

                        $this->layout->setTitle('Currency Exchange')->view('exchange/preview', $data);
                        return;
                    }
                }
            }
        }
        else if ($this->input->post('confirm')) {
            $code  = $this->input->post('code');
            $_code = $this->session->flashdata('exchange_code');

            if ($code != $_code) {
                $this->session->set_flashdata('error', 'Sorry but there was an unexpected error - please try again.');
                redirect('/exchange/' . $from, 'refresh');
            }

            $data = $this->exchange_model->getTemp($code);
            // Exchange not found (already processed or expired)
            if (!$data) {
                $this->session->set_flashdata('error', 'Sorry but there was an unexpected error - please try again.');
                redirect('/exchange/' . $from, 'refresh');
            }

            // Do more checks as above - to be sure
            if (bccomp($data->amount, $balances->{$from . '_available'}, 2) > 0) {
                $this->session->set_flashdata('error', 'Sorry but there was an unexpected error - please try again.');
                redirect('/exchange/' . $from, 'refresh');
            }
            else {
                $alreadyExchanged = $this->exchange_model->findTotalExchanges($this->userId);

                $newAmount = bcadd($alreadyExchanged, $data->amount, 2);

                if (bccomp($newAmount, '999', 2) > 0) {
                    $this->session->set_flashdata('error', 'Sorry but there was an unexpected error - please try again.');
                    redirect('/exchange/' . $from, 'refresh');
                }
                else {
                    if ($this->exchange_model->process((array)$data)) {
                        $this->session->set_flashdata('success', 'Your exchange has been completed.');
                        redirect($this->config->item('default_redirect'), 'refresh');
                    }
                }
            }
        }

        $longCurrencies = array();
        foreach ($currencies as $c) {
            $longCurrencies[$c] = code2Name($c);
        }

        $data['currencies'] = $longCurrencies;
        $data['currency']   = $currency;
        $data['rate']       = $rate;
        $data['from']       = $from;
        $data['balance']    = $balances->{$from . '_available'};

        $data['amount'] = array(
            'name'        => 'amount',
            'id'          => 'amount',
            'type'        => 'text',
            'class'       => 'form-control',
            'placeholder' => _l('p_amount'),
            'value'       => $this->form_validation->set_value('amount')
        );

        $this->layout->setTitle('Currency Exchange')->view('exchange/index', $data);
    }
}