<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class SessionMessages extends Widget
{
    function run()
    {
        $data = null;

        if ($flashMessage = $this->session->flashdata('info'))
        {
            $data[] = array(
                'class'   => 'alert-info',
                'message' => $flashMessage
            );
        }

        if ($flashMessage = $this->session->flashdata('warning'))
        {
            $data[] = array(
                'class'   => 'alert-warning',
                'message' => $flashMessage
            );
        }

        if ($flashMessage = $this->session->flashdata('success'))
        {
            $data[] = array(
                'class'   => 'alert-success',
                'message' => $flashMessage
            );
        }

        if ($flashMessage = $this->session->flashdata('error'))
        {
            $data[] = array(
                'class'   => 'alert-danger',
                'message' => $flashMessage
            );
        }

        if ($data)
            $this->render('messages', $data);
    }
}