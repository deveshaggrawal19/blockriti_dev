<?php
    function bcmax() {
        $args = func_get_args();
        if (count($args)<=2) return false;
        $precision = array_shift($args);
        $max = array_shift($args);
        foreach($args as $value) {
            if (bccomp($value, $max, $precision)==1) {
                $max = $value;
            }
        }
        return $max;
    }

    function bcmin() {
        $args = func_get_args();
        if (count($args)<=2) return false;
        $precision = array_shift($args);
        $min = array_shift($args);
        foreach($args as $value) {
            if (bccomp($min, $value, $precision)==1) {
                $min = $value;
            }
        }
        return $min;
    }

    function bcround($number, $precision = 0)
    {
        if (strpos($number, '.') !== false) {
            if ($number[0] != '-') return bcadd($number, '0.' . str_repeat('0', $precision) . '5', $precision);
            return bcsub($number, '0.' . str_repeat('0', $precision) . '5', $precision);
        }
        return $number;
    }