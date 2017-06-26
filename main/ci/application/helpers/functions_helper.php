<?php

    function milliseconds() {
        return floor(microtime(true) * 1000);
    }

    function replaceAccents($str) {
        $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
        $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
        return str_replace($a, $b, $str);
    }

    function getPrecision($currency) {
        if (in_array(strtolower($currency), array('usd', 'cad', 'gbp', 'mxn')) !== false) return 2;
        if (in_array(strtolower($currency), array('xau')) !== false) return 6;
        if (in_array(strtolower($currency), array('btc', 'ltc')) !== false) return 8;

        return 2;
    }

    function rCurrency($currency, $val, $sep = ',') {
        return number_format($val, getPrecision($currency), '.', $sep);
    }

    function displayCurrency($currency, $val, $trimZeros = false, $html = true) {
        $amount = rCurrency($currency, $val);
        if ($trimZeros && strpos($amount, '.') !== false)
            $amount = rtrim(rtrim($amount,'0'), '.');

        switch ($currency) {
            case 'mxn':
            case 'cad':
            case 'usd':
                //$formatted = '$' . $amount . '<span class="e">' . strtoupper($currency) . '</span>';
                $formatted = '$' . $amount;
                break;

            case 'btc':
                //$formatted = $amount . '<span class="e">BTC</span>';
                //$formatted = "Ƀ".$amount;
                $formatted = "&#579;".$amount;
                break;

            case 'xau':
                $formatted = $amount . '<span class="e">Oz</span>';
                break;

            default:
                $formatted = $amount . '<span class="e">' . strtoupper($currency) . '</span>';
                break;
        }

        return $html ? $formatted : strip_tags($formatted);
    }

    function displayErrors($errors) {
        if (is_null($errors)) return null;

        if (is_array($errors)) $_error = implode('<br/>', $errors);
        else $_error = $errors;

        $block = <<<ALERT
<div class="alert alert-danger alert-dismissable alert-form">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    {$_error}
</div>
ALERT;

        return $block;
    }

    function displaySuccess($success) {
        if (is_null($success)) return null;

        if (is_array($success)) $_success = implode('<br/>', $success);
        else $_success = $success;

        $block = <<<ALERT
<div class="alert alert-success alert-dismissable alert-form">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    {$_success}
</div>
ALERT;

        return $block;
    }

    function isCurrency($currency, $number) {
        $precision = getPrecision($currency);
        if ($precision > 0) return preg_match("/^0?[0-9]*(?:\.[0-9]{1," . $precision . "})?$/", $number);
        else return preg_match("/^[0-9]+$/", $number);
    }

    function checkPositive($currency, $amount) {
        return bccomp($amount, "0", getPrecision($currency)) === 1;
    }

    function generateNewPagination($url, $count, $page, $perPage) {
        if (strpos($url, "%d") === false) $url .= '/%d/%d';

        $pages = ceil($count / $perPage);

        $paging = array();

        if ($pages > 15) {
            $end_begin = min(4, $pages);
            for ($i = 1; $i <= $end_begin; $i++) {
                $pageUrl = sprintf($url, $i, $perPage);

                $paging[] = $i == $page ? '<li class="active"><a href="' . $pageUrl . '">' . $i . '</a></li>' : '<li><a href="' . $pageUrl . '">' . $i . '</a></li>';
            }

            // Middle
            $init_middle = max($end_begin + 1, $page - 2);
            $end_middle  = min($page + 2, $pages);

            if ($page > $end_begin + 2) $paging[] = '<li class="disabled"><span>...</span></li>';

            for ($i = $init_middle; $i <= $end_middle; $i++) {
                $pageUrl  = sprintf($url, $i, $perPage);
                $paging[] = $i == $page ? '<li class="active"><a href="' . $pageUrl . '">' . $i . '</a></li>' : '<li><a href="' . $pageUrl . '">' . $i . '</a></li>';
            }

            //Ending
            $init_ending = max($end_middle + 1, $pages - 2);
            if ($init_ending != $end_middle + 1) $paging[] = '<li class="disabled"><span>...</span></li>';

            for ($i = $init_ending; $i <= $pages; $i++) {
                $pageUrl  = sprintf($url, $i, $perPage);
                $paging[] = '<li><a href="' . $pageUrl . '">' . $i . '</a></li>';
            }
        }
        else {
            for ($i = 1; $i <= $pages; $i++) {
                $pageUrl = sprintf($url, $i, $perPage);

                $paging[] = $i == $page ? '<li class="active"><a href="' . $pageUrl . '">' . $i . '</a></li>' : '<li><a href="' . $pageUrl . '">' . $i . '</a></li>';
            }
        }

        $complete = '<ul class="pagination pagination-sm">' . implode('', $paging) . '</ul>';

        return $complete;
    }

    function verificationDoc($code, $lang='en') {
        switch ($lang) {
            case 'es':
                switch ($code) {
                    case 'UB':  return 'Recibo de luz';
                    case 'BS':  return 'Estado de cuenta bancaria';
                    case 'CCS': return 'Estado de cuenta tarjeta de crédito';
                    case 'PB':  return 'Recibo de Teléfono';
                    case 'VT':  return 'Factura vehicular';
                    case 'TR':  return 'Declaración de impuestos';
                    case 'SEL': return 'Carta de inscripción escolar';
                }

            default:
                return false;
        }
    }

    function code2Name($code, $lang='en') {
        switch ($lang) {
            case 'es':
                switch ($code) {
                    case 'btc': return 'Bitcoin';
                    case 'ltc': return 'Litecoin';
                    case 'bp':  return 'BillPay';
                    case 'dt':  return 'Transferencia Directa';
                    case 'mo':  return 'Giro Bancario';
                    case 'ch':  return 'Cheque';
                    case 'bw':  return 'Transferencia Bancaria';
                    case 'sp':  return 'Transferencia SPEI';
                    case 'ie':  return 'Interac e-Transfer';
                    case 'io':  return 'INTERAC Online';
                    case 'wu':  return 'Western Union';
                    case 'ip':  return 'En Persona';
                    case 'vi':  return 'VISA';
                    case 'vo':  return 'Voucher';
                    case 'cp':  return 'Compropago';
                    case 'pm':  return 'Pademobile';
                    case 'ap':  return 'Astropay';
                    case 'rp':  return 'Ripple';
                    case 'adm': return 'Admin Adjustment';
                    case 'cad': return 'Dólares Canadiense';
                    case 'mxn': return 'Pesos Mexicanos';
                    case 'eur': return 'Euros';
                    case 'usd': return 'Dólares Americanos';
                    default: return $code;
                }

            default:
                switch ($code) {
                    case 'bp':  return 'BillPay';
                    case 'dt':  return 'Direct Transfer';
                    case 'mo':  return 'Money Order';
                    case 'ch':  return 'Cheque';
                    case 'bw':  return 'Bank Wire';
                    case 'sp':  return 'SPEI Transfer';
                    case 'ie':  return 'Interac e-Transfer';
                    case 'io':  return 'INTERAC Online';
                    case 'wu':  return 'Western Union';
                    case 'ip':  return 'In-Person';
                    case 'vi':  return 'VISA';
                    case 'vo':  return 'Voucher';
                    case 'eft': return 'Electronic Funds Transfer';
                    case 'cp':  return 'Compropago';
                    case 'pm':  return 'Pademobile';
                    case 'ap':  return 'Astropay';
                    case 'rp':  return 'Ripple';
                    case 'ep':  return 'EgoPay';
                    case 'pz':  return 'Payza';
                    case 'mp':  return 'MoneyPolo';
                    case 'vg':  return 'Vogogo';
                    case 'cy':  return 'CryptoCapital';

                    case '1oz': return 'Mint 1oz Gold Bar';
                    case 'gld': return 'Gold Bullion';

                    case 'adm': return 'Admin Adjustment';
                    case 'cou': return 'Coupon';

                    case 'btc': return 'Bitcoin';
                    case 'ltc': return 'Litecoin';
                    
                    case 'ba': return 'Bitcoin (Admin)';
                    case 'ca': return 'CAD (Admin)';

                    case 'cad': return 'Canadian Dollars';
                    case 'mxn': return 'Mexican Peso';
                    case 'eur': return 'Euros';
                    case 'usd': return 'US Dollars';
                    case 'xau': return 'Oz of Gold';
                    default: return $code;
                }
        }
    }

    function makeSafeCurrency($text) {
        $text = str_replace('btc', 'xbt', $text);
        $text = str_replace('BTC', 'XBT', $text);

        return $text;
    }

    function asset($assetFile) {
        $baseUrl = str_replace(array('http://', 'https://'), '//', base_url());

        return $baseUrl . 'assets' . ASSETS . '/' . $assetFile;
    }

    function generateRandomString($length = 40, $noSigns = false) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!$&^*()_+][}{;:';
        if ($noSigns)
            $characters = substr($characters, 0, 36);

        $randomString = '';
        for ($i = 0; $i < $length; $i++)
            $randomString .= $characters[rand(0, strlen($characters) - 1)];

        return $randomString;
    }

    function states($country) {
        switch ($country) {
            case 'CA':
                return array(
                    'AB' => 'Alberta',
                    'BC' => 'British Columbia',
                    'MB' => 'Manitoba',
                    'NB' => 'New Brunswick',
                    'NL' => 'Newfoundland and Labrador',
                    'NS' => 'Nova Scotia',
                    'NT' => 'Northwest Territories',
                    'NU' => 'Nunavut',
                    'ON' => 'Ontario',
                    'PE' => 'Prince Edward Island',
                    'QC' => 'Quebec',
                    'SK' => 'Saskatchewan',
                    'YT' => 'Yukon'
                );

            case 'US':
                return array(
                    'AL' => 'Alabama',
                    'AK' => 'Alaska',
                    'AZ' => 'Arizona',
                    'AR' => 'Arkansas',
                    'CA' => 'California',
                    'CO' => 'Colorado',
                    'CT' => 'Connecticut',
                    'DC' => 'D.C.',
                    'DE' => 'Delaware',
                    'FL' => 'Florida',
                    'GA' => 'Georgia',
                    'HI' => 'Hawaii',
                    'ID' => 'Idaho',
                    'IL' => 'Illinois',
                    'IN' => 'Indiana',
                    'IA' => 'Iowa',
                    'KS' => 'Kansas',
                    'KY' => 'Kentucky',
                    'LA' => 'Louisiana',
                    'ME' => 'Maine',
                    'MD' => 'Maryland',
                    'MA' => 'Massachusetts',
                    'MI' => 'Michigan',
                    'MN' => 'Minnesota',
                    'MS' => 'Mississippi',
                    'MO' => 'Missouri',
                    'MT' => 'Montana',
                    'NE' => 'Nebraska',
                    'NV' => 'Nevada',
                    'NB' => 'New Brunswick',
                    'NH' => 'New Hampshire',
                    'NJ' => 'New Jersey',
                    'NM' => 'New Mexico',
                    'NY' => 'New York',
                    'NC' => 'North Carolina',
                    'ND' => 'North Dakota',
                    'NT' => 'Northwest Terr.',
                    'OH' => 'Ohio',
                    'OK' => 'Oklahoma',
                    'OR' => 'Oregon',
                    'PA' => 'Pennsylvania',
                    'RI' => 'Rhode Island',
                    'SC' => 'South Carolina',
                    'SD' => 'South Dakota',
                    'TN' => 'Tennessee',
                    'TX' => 'Texas',
                    'UT' => 'Utah',
                    'VT' => 'Vermont',
                    'VA' => 'Virginia',
                    'WA' => 'Washington',
                    'WV' => 'West Virginia',
                    'WI' => 'Wisconsin',
                    'WY' => 'Wyoming');

            default:
                return array();
        }
    }

    function countriesLocalised($language = 'en') {
        return array_map(function($item) use ($language) {
            return $item[$language];
        }, countries());
    }

    function countryName($iso2, $language = 'en') {
        $countries = countries();
        return $countries[$iso2][$language];
    }

    function countryCode($code, $language = 'en') {
        $countries = countriesLocalised($language);
        $countries = array_flip($countries);

        return $countries[$code];
    }
    function countries() {
        return array(
            'AF' => array(
                'en'   => 'Afghanistan',
                'es'   => 'Afganistán',
                'iso3' => 'AFG'
            ),
            'AX' => array(
                'en'   => 'Åland Islands',
                'es'   => 'Akrotiri',
                'iso3' => 'ALA'
            ),
            'AL' => array(
                'en'   => 'Albania',
                'es'   => 'Albania',
                'iso3' => 'ALB'
            ),
            'DZ' => array(
                'en'   => 'Algeria',
                'es'   => 'Alemania',
                'iso3' => 'DZA'
            ),
            'AS' => array(
                'en'   => 'American Samoa',
                'es'   => 'American Samoa',
                'iso3' => 'ASM'
            ),
            'AD' => array(
                'en'   => 'Andorra',
                'es'   => 'Andorra',
                'iso3' => 'AND'
            ),
            'AO' => array(
                'en'   => 'Angola',
                'es'   => 'Angola',
                'iso3' => 'AGO'
            ),
            'AI' => array(
                'en'   => 'Anguilla',
                'es'   => 'Anguila',
                'iso3' => 'AIA'
            ),
            'AQ' => array(
                'en'   => 'Antarctica',
                'es'   => 'Antártida',
                'iso3' => 'ATA'
            ),
            'AG' => array(
                'en'   => 'Antigua and Barbuda',
                'es'   => 'Antigua y Barbuda',
                'iso3' => 'ATG'
            ),
            'AR' => array(
                'en'   => 'Argentina',
                'es'   => 'Argentina',
                'iso3' => 'ARG'
            ),
            'AM' => array(
                'en'   => 'Armenia',
                'es'   => 'Armenia',
                'iso3' => 'ARM'
            ),
            'AW' => array(
                'en'   => 'Aruba',
                'es'   => 'Aruba',
                'iso3' => 'ABW'
            ),
            'AU' => array(
                'en'   => 'Australia',
                'es'   => 'Australia',
                'iso3' => 'AUS'
            ),
            'AT' => array(
                'en'   => 'Austria',
                'es'   => 'Austria',
                'iso3' => 'AUT'
            ),
            'AZ' => array(
                'en'   => 'Azerbaijan',
                'es'   => 'Azerbaiyán',
                'iso3' => 'AZE'
            ),
            'BS' => array(
                'en'   => 'Bahamas',
                'es'   => 'Bahamas',
                'iso3' => 'BHS'
            ),
            'BH' => array(
                'en'   => 'Bahrain',
                'es'   => 'Bahráin',
                'iso3' => 'BHR'
            ),
            'BD' => array(
                'en'   => 'Bangladesh',
                'es'   => 'Bangladesh',
                'iso3' => 'BGD'
            ),
            'BB' => array(
                'en'   => 'Barbados',
                'es'   => 'Barbados',
                'iso3' => 'BRB'
            ),
            'BY' => array(
                'en'   => 'Belarus',
                'es'   => 'Bielorrusia',
                'iso3' => 'BLR'
            ),
            'BE' => array(
                'en'   => 'Belgium',
                'es'   => 'Bélgica',
                'iso3' => 'BEL'
            ),
            'BZ' => array(
                'en'   => 'Belize',
                'es'   => 'Belice',
                'iso3' => 'BLZ'
            ),
            'BJ' => array(
                'en'   => 'Benin',
                'es'   => 'Benin',
                'iso3' => 'BEN'
            ),
            'BM' => array(
                'en'   => 'Bermuda',
                'es'   => 'Bermuda',
                'iso3' => 'BMU'
            ),
            'BT' => array(
                'en'   => 'Bhutan',
                'es'   => 'Bhutan',
                'iso3' => 'BTN'
            ),
            'BO' => array(
                'en'   => 'Bolivia, Plurinational State of',
                'es'   => 'Bolivia',
                'iso3' => 'BOL'
            ),
            'BQ' => array(
                'en'   => 'Bonaire, Sint Eustatius and Saba',
                'es'   => 'Bonaire, Sint Eustatius y Saba',
                'iso3' => 'BES'
            ),
            'BA' => array(
                'en'   => 'Bosnia and Herzegovina',
                'es'   => 'Bosnia y Herzegovina',
                'iso3' => 'BIH'
            ),
            'BW' => array(
                'en'   => 'Botswana',
                'es'   => 'Botswana',
                'iso3' => 'BWA'
            ),
            'BV' => array(
                'en'   => 'Bouvet Island',
                'es'   => 'Bouvet Island',
                'iso3' => 'BVT'
            ),
            'BR' => array(
                'en'   => 'Brazil',
                'es'   => 'Brasil',
                'iso3' => 'BRA'
            ),
            'IO' => array(
                'en'   => 'British Indian Ocean Territory',
                'es'   => 'Territorio Británico del Océano Índico',
                'iso3' => 'IOT'
            ),
            'BN' => array(
                'en'   => 'Brunei Darussalam',
                'es'   => 'Brunei Darussalam',
                'iso3' => 'BRN'
            ),
            'BG' => array(
                'en'   => 'Bulgaria',
                'es'   => 'Bulgaria',
                'iso3' => 'BGR'
            ),
            'BF' => array(
                'en'   => 'Burkina Faso',
                'es'   => 'Burkina Faso',
                'iso3' => 'BFA'
            ),
            'BI' => array(
                'en'   => 'Burundi',
                'es'   => 'Burundi',
                'iso3' => 'BDI'
            ),
            'KH' => array(
                'en'   => 'Cambodia',
                'es'   => 'Camboya',
                'iso3' => 'KHM'
            ),
            'CM' => array(
                'en'   => 'Cameroon',
                'es'   => 'Camerún',
                'iso3' => 'CMR'
            ),
            'CA' => array(
                'en'   => 'Canada',
                'es'   => 'Canadá',
                'iso3' => 'CAN'
            ),
            'CV' => array(
                'en'   => 'Cape Verde',
                'es'   => 'Cabo Verde',
                'iso3' => 'CPV'
            ),
            'KY' => array(
                'en'   => 'Cayman Islands',
                'es'   => 'Islas Caimán',
                'iso3' => 'CYM'
            ),
            'CF' => array(
                'en'   => 'Central African Republic',
                'es'   => 'República Centroafricana',
                'iso3' => 'CAF'
            ),
            'TD' => array(
                'en'   => 'Chad',
                'es'   => 'Chad',
                'iso3' => 'TCD'
            ),
            'CL' => array(
                'en'   => 'Chile',
                'es'   => 'Chile',
                'iso3' => 'CHL'
            ),
            'CN' => array(
                'en'   => 'China',
                'es'   => 'China',
                'iso3' => 'CHN'
            ),
            'CX' => array(
                'en'   => 'Christmas Island',
                'es'   => 'Isla de Navidad',
                'iso3' => 'CXR'
            ),
            'CC' => array(
                'en'   => 'Cocos (Keeling) Islands',
                'es'   => 'Islas Cocos (Keeling)',
                'iso3' => 'CCK'
            ),
            'CO' => array(
                'en'   => 'Colombia',
                'es'   => 'Colombia',
                'iso3' => 'COL'
            ),
            'KM' => array(
                'en'   => 'Comoros',
                'es'   => 'Comoras',
                'iso3' => 'COM'
            ),
            'CG' => array(
                'en'   => 'Congo',
                'es'   => 'Congo',
                'iso3' => 'COG'
            ),
            'CD' => array(
                'en'   => 'Congo, the Democratic Republic of the',
                'es'   => 'Congo, la República Democrática del',
                'iso3' => 'COD'
            ),
            'CK' => array(
                'en'   => 'Cook Islands',
                'es'   => 'Islas Cook',
                'iso3' => 'COK'
            ),
            'CR' => array(
                'en'   => 'Costa Rica',
                'es'   => 'Costa Rica',
                'iso3' => 'CRI'
            ),
            'CI' => array(
                'en'   => "Ivory Coast",
                'es'   => "Côte d'Ivoire",
                'iso3' => 'CIV'
            ),
            'HR' => array(
                'en'   => 'Croatia',
                'es'   => 'Croacia',
                'iso3' => 'HRV'
            ),
            'CU' => array(
                'en'   => 'Cuba',
                'es'   => 'Cuba',
                'iso3' => 'CUB'
            ),
            'CW' => array(
                'en'   => 'Curaçao',
                'es'   => 'Curaçao',
                'iso3' => 'CUW'
            ),
            'CY' => array(
                'en'   => 'Cyprus',
                'es'   => 'Chipre',
                'iso3' => 'CYP'
            ),
            'CZ' => array(
                'en'   => 'Czech Republic',
                'es'   => 'República Checa',
                'iso3' => 'CZE'
            ),
            'DK' => array(
                'en'   => 'Denmark',
                'es'   => 'Dinamarca',
                'iso3' => 'DNK'
            ),
            'DJ' => array(
                'en'   => 'Djibouti',
                'es'   => 'Djibouti',
                'iso3' => 'DJI'
            ),
            'DM' => array(
                'en'   => 'Dominica',
                'es'   => 'Dominica',
                'iso3' => 'DMA'
            ),
            'DO' => array(
                'en'   => 'Dominican Republic',
                'es'   => 'República Dominicana',
                'iso3' => 'DOM'
            ),
            'EC' => array(
                'en'   => 'Ecuador',
                'es'   => 'Ecuador',
                'iso3' => 'ECU'
            ),
            'EG' => array(
                'en'   => 'Egypt',
                'es'   => 'Egipto',
                'iso3' => 'EGY'
            ),
            'SV' => array(
                'en'   => 'El Salvador',
                'es'   => 'El Salvador',
                'iso3' => 'SLV'
            ),
            'GQ' => array(
                'en'   => 'Equatorial Guinea',
                'es'   => 'Guinea Ecuatorial',
                'iso3' => 'GNQ'
            ),
            'ER' => array(
                'en'   => 'Eritrea',
                'es'   => 'Eritrea',
                'iso3' => 'ERI'
            ),
            'EE' => array(
                'en'   => 'Estonia',
                'es'   => 'Estonia',
                'iso3' => 'EST'
            ),
            'ET' => array(
                'en'   => 'Ethiopia',
                'es'   => 'Etiopía',
                'iso3' => 'ETH'
            ),
            'FK' => array(
                'en'   => 'Falkland Islands (Malvinas)',
                'es'   => 'Islas Malvinas (Falkland)',
                'iso3' => 'FLK'
            ),
            'FO' => array(
                'en'   => 'Faroe Islands',
                'es'   => 'Islas Feroe',
                'iso3' => 'FRO'
            ),
            'FJ' => array(
                'en'   => 'Fiji',
                'es'   => 'Fiji',
                'iso3' => 'FJI'
            ),
            'FI' => array(
                'en'   => 'Finland',
                'es'   => 'Finlandia',
                'iso3' => 'FIN'
            ),
            'FR' => array(
                'en'   => 'France',
                'es'   => 'France',
                'iso3' => 'FRA'
            ),
            'GF' => array(
                'en'   => 'French Guiana',
                'es'   => 'Guiana francés',
                'iso3' => 'GUF'
            ),
            'PF' => array(
                'en'   => 'French Polynesia',
                'es'   => 'Polinesia francés',
                'iso3' => 'PYF'
            ),
            'TF' => array(
                'en'   => 'French Southern Territories',
                'es'   => 'Territorios Australes Franceses',
                'iso3' => 'ATF'
            ),
            'GA' => array(
                'en'   => 'Gabon',
                'es'   => 'Gabón',
                'iso3' => 'GAB'
            ),
            'GM' => array(
                'en'   => 'Gambia',
                'es'   => 'Gambia',
                'iso3' => 'GMB'
            ),
            'GE' => array(
                'en'   => 'Georgia',
                'es'   => 'Georgia',
                'iso3' => 'GEO'
            ),
            'DE' => array(
                'en'   => 'Germany',
                'es'   => 'Alemania',
                'iso3' => 'DEU'
            ),
            'GH' => array(
                'en'   => 'Ghana',
                'es'   => 'Ghana',
                'iso3' => 'GHA'
            ),
            'GI' => array(
                'en'   => 'Gibraltar',
                'es'   => 'Gibraltar',
                'iso3' => 'GIB'
            ),
            'GR' => array(
                'en'   => 'Greece',
                'es'   => 'Grecia',
                'iso3' => 'GRC'
            ),
            'GL' => array(
                'en'   => 'Greenland',
                'es'   => 'Groenlandia',
                'iso3' => 'GRL'
            ),
            'GD' => array(
                'en'   => 'Grenada',
                'es'   => 'Grenada',
                'iso3' => 'GRD'
            ),
            'GP' => array(
                'en'   => 'Guadeloupe',
                'es'   => 'Guadalupe',
                'iso3' => 'GLP'
            ),
//            'GU' => array(
//                'en'   => 'Guam',
//                'es'   => 'Guam',
//                'iso3' => 'GUM'
//            ),
            'GT' => array(
                'en'   => 'Guatemala',
                'es'   => 'Guatemala',
                'iso3' => 'GTM'
            ),
            'GG' => array(
                'en'   => 'Guernsey',
                'es'   => 'Guernsey',
                'iso3' => 'GGY'
            ),
            'GN' => array(
                'en'   => 'Guinea',
                'es'   => 'Guinea',
                'iso3' => 'GIN'
            ),
            'GW' => array(
                'en'   => 'Guinea-Bissau',
                'es'   => 'Guinea-Bissau',
                'iso3' => 'GNB'
            ),
            'GY' => array(
                'en'   => 'Guyana',
                'es'   => 'Guayana',
                'iso3' => 'GUY'
            ),
            'HT' => array(
                'en'   => 'Haiti',
                'es'   => 'Haiti',
                'iso3' => 'HTI'
            ),
            'HM' => array(
                'en'   => 'Heard Island and McDonald Islands',
                'es'   => 'Islas Heard y McDonald',
                'iso3' => 'HMD'
            ),
            'VA' => array(
                'en'   => 'Holy See (Vatican City State)',
                'es'   => 'Santa Sede (Ciudad del Vaticano)',
                'iso3' => 'VAT'
            ),
            'HN' => array(
                'en'   => 'Honduras',
                'es'   => 'Honduras',
                'iso3' => 'HND'
            ),
            'HK' => array(
                'en'   => 'Hong Kong',
                'es'   => 'Hong Kong',
                'iso3' => 'HKG'
            ),
            'HU' => array(
                'en'   => 'Hungary',
                'es'   => 'Hungría',
                'iso3' => 'HUN'
            ),
            'IS' => array(
                'en'   => 'Iceland',
                'es'   => 'Islandia',
                'iso3' => 'ISL'
            ),
            'IN' => array(
                'en'   => 'India',
                'es'   => 'India',
                'iso3' => 'IND'
            ),
            'ID' => array(
                'en'   => 'Indonesia',
                'es'   => 'Indonesia',
                'iso3' => 'IDN'
            ),
            'IR' => array(
                'en'   => 'Iran, Islamic Republic of',
                'es'   => 'Irán, República Islámica de',
                'iso3' => 'IRN'
            ),
            'IQ' => array(
                'en'   => 'Iraq',
                'es'   => 'Irak',
                'iso3' => 'IRQ'
            ),
            'IE' => array(
                'en'   => 'Ireland',
                'es'   => 'Irlanda',
                'iso3' => 'IRL'
            ),
            'IM' => array(
                'en'   => 'Isle of Man',
                'es'   => 'Isla de Man',
                'iso3' => 'IMN'
            ),
            'IL' => array(
                'en'   => 'Israel',
                'es'   => 'Israel',
                'iso3' => 'ISR'
            ),
            'IT' => array(
                'en'   => 'Italy',
                'es'   => 'Italia',
                'iso3' => 'ITA'
            ),
            'JM' => array(
                'en'   => 'Jamaica',
                'es'   => 'Jamaica',
                'iso3' => 'JAM'
            ),
            'JP' => array(
                'en'   => 'Japan',
                'es'   => 'Japón',
                'iso3' => 'JPN'
            ),
            'JE' => array(
                'en'   => 'Jersey',
                'es'   => 'Jersey',
                'iso3' => 'JEY'
            ),
            'JO' => array(
                'en'   => 'Jordan',
                'es'   => 'Jordania',
                'iso3' => 'JOR'
            ),
            'KZ' => array(
                'en'   => 'Kazakhstan',
                'es'   => 'Kazajstán',
                'iso3' => 'KAZ'
            ),
            'KE' => array(
                'en'   => 'Kenya',
                'es'   => 'Kenia',
                'iso3' => 'KEN'
            ),
            'KI' => array(
                'en'   => 'Kiribati',
                'es'   => 'Kiribati',
                'iso3' => 'KIR'
            ),
            'KP' => array(
                'en'   => "Korea, Democratic People's Republic of",
                'es'   => "Corea, República Popular Democrática de",
                'iso3' => 'PRK'
            ),
            'KR' => array(
                'en'   => 'Korea, Republic of',
                'es'   => 'Corea, República de',
                'iso3' => 'KOR'
            ),
            'KW' => array(
                'en'   => 'Kuwait',
                'es'   => 'Kuwait',
                'iso3' => 'KWT'
            ),
            'KG' => array(
                'en'   => 'Kyrgyzstan',
                'es'   => 'Kirguistán',
                'iso3' => 'KGZ'
            ),
            'LA' => array(
                'en'   => "Lao People's Democratic Republic",
                'es'   => "República Democrática Popular Lao",
                'iso3' => 'LAO'
            ),
            'LV' => array(
                'en'   => 'Latvia',
                'es'   => 'Letonia',
                'iso3' => 'LVA'
            ),
            'LB' => array(
                'en'   => 'Lebanon',
                'es'   => 'Líbano',
                'iso3' => 'LBN'
            ),
            'LS' => array(
                'en'   => 'Lesotho',
                'es'   => 'Lesoto',
                'iso3' => 'LSO'
            ),
            'LR' => array(
                'en'   => 'Liberia',
                'es'   => 'Liberia',
                'iso3' => 'LBR'
            ),
            'LY' => array(
                'en'   => 'Libya',
                'es'   => 'Libia',
                'iso3' => 'LBY'
            ),
            'LI' => array(
                'en'   => 'Liechtenstein',
                'es'   => 'Liechtenstein',
                'iso3' => 'LIE'
            ),
            'LT' => array(
                'en'   => 'Lithuania',
                'es'   => 'Lituania',
                'iso3' => 'LTU'
            ),
            'LU' => array(
                'en'   => 'Luxembourg',
                'es'   => 'Luxemburgo',
                'iso3' => 'LUX'
            ),
            'MO' => array(
                'en'   => 'Macao',
                'es'   => 'Macao',
                'iso3' => 'MAC'
            ),
            'MK' => array(
                'en'   => 'Macedonia, the former Yugoslav Republic of',
                'es'   => 'Macedonia, Antigua República Yugoslava de',
                'iso3' => 'MKD'
            ),
            'MG' => array(
                'en'   => 'Madagascar',
                'es'   => 'Madagascar',
                'iso3' => 'MDG'
            ),
            'MW' => array(
                'en'   => 'Malawi',
                'es'   => 'Malawi',
                'iso3' => 'MWI'
            ),
            'MY' => array(
                'en'   => 'Malaysia',
                'es'   => 'Malasia',
                'iso3' => 'MYS'
            ),
            'MV' => array(
                'en'   => 'Maldives',
                'es'   => 'Maldivas',
                'iso3' => 'MDV'
            ),
            'ML' => array(
                'en'   => 'Mali',
                'es'   => 'Mali',
                'iso3' => 'MLI'
            ),
            'MT' => array(
                'en'   => 'Malta',
                'es'   => 'Malta',
                'iso3' => 'MLT'
            ),
            'MH' => array(
                'en'   => 'Marshall Islands',
                'es'   => 'Islas Marshall',
                'iso3' => 'MHL'
            ),
            'MQ' => array(
                'en'   => 'Martinique',
                'es'   => 'Martinica',
                'iso3' => 'MTQ'
            ),
            'MR' => array(
                'en'   => 'Mauritania',
                'es'   => 'Mauritania',
                'iso3' => 'MRT'
            ),
            'MU' => array(
                'en'   => 'Mauritius',
                'es'   => 'Mauricio',
                'iso3' => 'MUS'
            ),
            'YT' => array(
                'en'   => 'Mayotte',
                'es'   => 'Mayotte',
                'iso3' => 'MYT'
            ),
            'MX' => array(
                'en'   => 'Mexico',
                'es'   => 'México',
                'iso3' => 'MEX'
            ),
            'FM' => array(
                'en'   => 'Micronesia, Federated States of',
                'es'   => 'Micronesia, Federated States of',
                'iso3' => 'FSM'
            ),
            'MD' => array(
                'en'   => 'Moldova, Republic of',
                'es'   => 'Moldova, República de',
                'iso3' => 'MDA'
            ),
            'MC' => array(
                'en'   => 'Monaco',
                'es'   => 'Mónaco',
                'iso3' => 'MCO'
            ),
            'MN' => array(
                'en'   => 'Mongolia',
                'es'   => 'Mongolia',
                'iso3' => 'MNG'
            ),
            'ME' => array(
                'en'   => 'Montenegro',
                'es'   => 'Montenegro',
                'iso3' => 'MNE'
            ),
            'MS' => array(
                'en'   => 'Montserrat',
                'es'   => 'Montserrat',
                'iso3' => 'MSR'
            ),
            'MA' => array(
                'en'   => 'Morocco',
                'es'   => 'Marruecos',
                'iso3' => 'MAR'
            ),
            'MZ' => array(
                'en'   => 'Mozambique',
                'es'   => 'Mozambique',
                'iso3' => 'MOZ'
            ),
            'MM' => array(
                'en'   => 'Myanmar',
                'es'   => 'Myanmar',
                'iso3' => 'MMR'
            ),
            'NA' => array(
                'en'   => 'Namibia',
                'es'   => 'Namibia',
                'iso3' => 'NAM'
            ),
            'NR' => array(
                'en'   => 'Nauru',
                'es'   => 'Nauru',
                'iso3' => 'NRU'
            ),
            'NP' => array(
                'en'   => 'Nepal',
                'es'   => 'Nepal',
                'iso3' => 'NPL'
            ),
            'NL' => array(
                'en'   => 'Netherlands',
                'es'   => 'Países Bajos',
                'iso3' => 'NLD'
            ),
            'NC' => array(
                'en'   => 'New Caledonia',
                'es'   => 'Nueva Caledonia',
                'iso3' => 'NCL'
            ),
            'NZ' => array(
                'en'   => 'New Zealand',
                'es'   => 'Nueva Zelanda',
                'iso3' => 'NZL'
            ),
            'NI' => array(
                'en'   => 'Nicaragua',
                'es'   => 'Nicaragua',
                'iso3' => 'NIC'
            ),
            'NE' => array(
                'en'   => 'Niger',
                'es'   => 'Níger',
                'iso3' => 'NER'
            ),
            'NG' => array(
                'en'   => 'Nigeria',
                'es'   => 'Nigeria',
                'iso3' => 'NGA'
            ),
            'NU' => array(
                'en'   => 'Niue',
                'es'   => 'Niue',
                'iso3' => 'NIU'
            ),
            'NF' => array(
                'en'   => 'Norfolk Island',
                'es'   => 'Isla Norfolk',
                'iso3' => 'NFK'
            ),
            'MP' => array(
                'en'   => 'Northern Mariana Islands',
                'es'   => 'Islas Marianas del Norte',
                'iso3' => 'MNP'
            ),
            'NO' => array(
                'en'   => 'Norway',
                'es'   => 'Noruega',
                'iso3' => 'NOR'
            ),
            'OM' => array(
                'en'   => 'Oman',
                'es'   => 'Omán',
                'iso3' => 'OMN'
            ),
            'PK' => array(
                'en'   => 'Pakistan',
                'es'   => 'Pakistán',
                'iso3' => 'PAK'
            ),
            'PW' => array(
                'en'   => 'Palau',
                'es'   => 'Palau',
                'iso3' => 'PLW'
            ),
            'PS' => array(
                'en'   => 'Palestine, State of',
                'es'   => 'Palestina',
                'iso3' => 'PSE'
            ),
            'PA' => array(
                'en'   => 'Panama',
                'es'   => 'Panamá',
                'iso3' => 'PAN'
            ),
            'PG' => array(
                'en'   => 'Papua New Guinea',
                'es'   => 'Papúa-Nueva Guinea',
                'iso3' => 'PNG'
            ),
            'PY' => array(
                'en'   => 'Paraguay',
                'es'   => 'Paraguay',
                'iso3' => 'PRY'
            ),
            'PE' => array(
                'en'   => 'Peru',
                'es'   => 'Perú',
                'iso3' => 'PER'
            ),
            'PH' => array(
                'en'   => 'Philippines',
                'es'   => 'Filipinas',
                'iso3' => 'PHL'
            ),
            'PN' => array(
                'en'   => 'Pitcairn',
                'es'   => 'Pitcairn',
                'iso3' => 'PCN'
            ),
            'PL' => array(
                'en'   => 'Poland',
                'es'   => 'Polonia',
                'iso3' => 'POL'
            ),
            'PT' => array(
                'en'   => 'Portugal',
                'es'   => 'Portugal',
                'iso3' => 'PRT'
            ),
//            'PR' => array(
//                'en'   => 'Puerto Rico',
//                'es'   => 'Puerto Rico',
//                'iso3' => 'PRI'
//            ),
            'QA' => array(
                'en'   => 'Qatar',
                'es'   => 'Katar',
                'iso3' => 'QAT'
            ),
            'RE' => array(
                'en'   => 'Réunion',
                'es'   => 'Reunión',
                'iso3' => 'REU'
            ),
            'RO' => array(
                'en'   => 'Romania',
                'es'   => 'Rumania',
                'iso3' => 'ROU'
            ),
            'RU' => array(
                'en'   => 'Russian Federation',
                'es'   => 'Rusia',
                'iso3' => 'RUS'
            ),
            'RW' => array(
                'en'   => 'Rwanda',
                'es'   => 'Ruanda',
                'iso3' => 'RWA'
            ),
            'BL' => array(
                'en'   => 'Saint Barthélemy',
                'es'   => 'San Bartolomé',
                'iso3' => 'BLM'
            ),
            'SH' => array(
                'en'   => 'Saint Helena, Ascension and Tristan da Cunha',
                'es'   => 'Santa Elena',
                'iso3' => 'SHN'
            ),
            'KN' => array(
                'en'   => 'Saint Kitts and Nevis',
                'es'   => 'San Cristóbal y Nieves',
                'iso3' => 'KNA'
            ),
            'LC' => array(
                'en'   => 'Saint Lucia',
                'es'   => 'Santa Lucía',
                'iso3' => 'LCA'
            ),
            'MF' => array(
                'en'   => 'Saint Martin (French part)',
                'es'   => 'Saint Martin (French part)',
                'iso3' => 'MAF'
            ),
            'PM' => array(
                'en'   => 'Saint Pierre and Miquelon',
                'es'   => 'San Pedro y Miquelón',
                'iso3' => 'SPM'
            ),
            'VC' => array(
                'en'   => 'Saint Vincent and the Grenadines',
                'es'   => 'San Vicente y las Granadinas',
                'iso3' => 'VCT'
            ),
            'WS' => array(
                'en'   => 'Samoa',
                'es'   => 'Samoa',
                'iso3' => 'WSM'
            ),
            'SM' => array(
                'en'   => 'San Marino',
                'es'   => 'San Marino',
                'iso3' => 'SMR'
            ),
            'ST' => array(
                'en'   => 'Sao Tome and Principe',
                'es'   => 'Santo Tomé y Príncipe',
                'iso3' => 'STP'
            ),
            'SA' => array(
                'en'   => 'Saudi Arabia',
                'es'   => 'Arabia Saudita',
                'iso3' => 'SAU'
            ),
            'SN' => array(
                'en'   => 'Senegal',
                'es'   => 'Senegal',
                'iso3' => 'SEN'
            ),
            'RS' => array(
                'en'   => 'Serbia',
                'es'   => 'Serbia',
                'iso3' => 'SRB'
            ),
            'SC' => array(
                'en'   => 'Seychelles',
                'es'   => 'Seychelles',
                'iso3' => 'SYC'
            ),
            'SL' => array(
                'en'   => 'Sierra Leone',
                'es'   => 'Sierra Leona',
                'iso3' => 'SLE'
            ),
            'SG' => array(
                'en'   => 'Singapore',
                'es'   => 'Singapur',
                'iso3' => 'SGP'
            ),
            'SX' => array(
                'en'   => 'Sint Maarten (Dutch part)',
                'es'   => 'Sint Maarten',
                'iso3' => 'SXM'
            ),
            'SK' => array(
                'en'   => 'Slovakia',
                'es'   => 'Eslovaquia',
                'iso3' => 'SVK'
            ),
            'SI' => array(
                'en'   => 'Slovenia',
                'es'   => 'Eslovenia',
                'iso3' => 'SVN'
            ),
            'SB' => array(
                'en'   => 'Solomon Islands',
                'es'   => 'Islas Salomón',
                'iso3' => 'SLB'
            ),
            'SO' => array(
                'en'   => 'Somalia',
                'es'   => 'Somalia',
                'iso3' => 'SOM'
            ),
            'ZA' => array(
                'en'   => 'South Africa',
                'es'   => 'Sudáfrica',
                'iso3' => 'ZAF'
            ),
            'GS' => array(
                'en'   => 'South Georgia and the South Sandwich Islands',
                'es'   => 'Georgia del Sur e Islas Sandwich del Sur',
                'iso3' => 'SGS'
            ),
            'SS' => array(
                'en'   => 'South Sudan',
                'es'   => 'Sudán del Sur',
                'iso3' => 'SSD'
            ),
            'ES' => array(
                'en'   => 'Spain',
                'es'   => 'España',
                'iso3' => 'ESP'
            ),
            'LK' => array(
                'en'   => 'Sri Lanka',
                'es'   => 'Sri Lanka',
                'iso3' => 'LKA'
            ),
            'SD' => array(
                'en'   => 'Sudan',
                'es'   => 'Sudán',
                'iso3' => 'SDN'
            ),
            'SR' => array(
                'en'   => 'Suriname',
                'es'   => 'Suriname',
                'iso3' => 'SUR'
            ),
            'SJ' => array(
                'en'   => 'Svalbard and Jan Mayen',
                'es'   => 'Svalbard y Jan Mayen',
                'iso3' => 'SJM'
            ),
            'SZ' => array(
                'en'   => 'Swaziland',
                'es'   => 'Swazilandia',
                'iso3' => 'SWZ'
            ),
            'SE' => array(
                'en'   => 'Sweden',
                'es'   => 'Suecia',
                'iso3' => 'SWE'
            ),
            'CH' => array(
                'en'   => 'Switzerland',
                'es'   => 'Suiza',
                'iso3' => 'CHE'
            ),
            'SY' => array(
                'en'   => 'Syrian Arab Republic',
                'es'   => 'República Árabe Siria',
                'iso3' => 'SYR'
            ),
            'TW' => array(
                'en'   => 'Taiwan, Province of China',
                'es'   => 'Taiwan, Provincia de China',
                'iso3' => 'TWN'
            ),
            'TJ' => array(
                'en'   => 'Tajikistan',
                'es'   => 'Tayikistán',
                'iso3' => 'TJK'
            ),
            'TZ' => array(
                'en'   => 'Tanzania, United Republic of',
                'es'   => 'Tanzania, República Unida de',
                'iso3' => 'TZA'
            ),
            'TH' => array(
                'en'   => 'Thailand',
                'es'   => 'Tailandia',
                'iso3' => 'THA'
            ),
            'TL' => array(
                'en'   => 'Timor-Leste',
                'es'   => 'Timor-Leste',
                'iso3' => 'TLS'
            ),
            'TG' => array(
                'en'   => 'Togo',
                'es'   => 'Togo',
                'iso3' => 'TGO'
            ),
            'TK' => array(
                'en'   => 'Tokelau',
                'es'   => 'Tokelau',
                'iso3' => 'TKL'
            ),
            'TO' => array(
                'en'   => 'Tonga',
                'es'   => 'Tonga',
                'iso3' => 'TON'
            ),
            'TT' => array(
                'en'   => 'Trinidad and Tobago',
                'es'   => 'Trinidad y Tobago',
                'iso3' => 'TTO'
            ),
            'TN' => array(
                'en'   => 'Tunisia',
                'es'   => 'Túnez',
                'iso3' => 'TUN'
            ),
            'TR' => array(
                'en'   => 'Turkey',
                'es'   => 'Turquía',
                'iso3' => 'TUR'
            ),
            'TM' => array(
                'en'   => 'Turkmenistan',
                'es'   => 'Turkmenistán',
                'iso3' => 'TKM'
            ),
            'TC' => array(
                'en'   => 'Turks and Caicos Islands',
                'es'   => 'Turcas y Caicos',
                'iso3' => 'TCA'
            ),
            'TV' => array(
                'en'   => 'Tuvalu',
                'es'   => 'Tuvalu',
                'iso3' => 'TUV'
            ),
            'UG' => array(
                'en'   => 'Uganda',
                'es'   => 'Uganda',
                'iso3' => 'UGA'
            ),
            'UA' => array(
                'en'   => 'Ukraine',
                'es'   => 'Ucrania',
                'iso3' => 'UKR'
            ),
            'AE' => array(
                'en'   => 'United Arab Emirates',
                'es'   => 'Emiratos Árabes Unidos',
                'iso3' => 'ARE'
            ),
            'GB' => array(
                'en'   => 'United Kingdom',
                'es'   => 'Reino Unido',
                'iso3' => 'GBR'
            ),
//            'US' => array(
//                'en'   => 'United States',
//                'es'   => 'Estados Unidos',
//                'iso3' => 'USA'
//            ),
//            'UM' => array(
//                'en'   => 'United States Minor Outlying Islands',
//                'es'   => 'Estados Unidos Islas menores alejadas de los',
//                'iso3' => 'UMI'
//            ),
            'UY' => array(
                'en'   => 'Uruguay',
                'es'   => 'Uruguay',
                'iso3' => 'URY'
            ),
            'UZ' => array(
                'en'   => 'Uzbekistan',
                'es'   => 'Uzbekistán',
                'iso3' => 'UZB'
            ),
            'VU' => array(
                'en'   => 'Vanuatu',
                'es'   => 'Vanuatu',
                'iso3' => 'VUT'
            ),
            'VE' => array(
                'en'   => 'Venezuela, Bolivarian Republic of',
                'es'   => 'Venezuela, Bolivarian Republic of',
                'iso3' => 'VEN'
            ),
            'VN' => array(
                'en'   => 'Vietnam',
                'es'   => 'Vietnam',
                'iso3' => 'VNM'
            ),
            'VG' => array(
                'en'   => 'Virgin Islands, British',
                'es'   => 'Islas Vírgenes Británicas',
                'iso3' => 'VGB'
            ),
//            'VI' => array(
//                'en'   => 'Virgin Islands, U.S.',
//                'es'   => 'Islas Vírgenes, EE.UU.',
//                'iso3' => 'VIR'
//            ),
            'WF' => array(
                'en'   => 'Wallis and Futuna',
                'es'   => 'Wallis y Futuna',
                'iso3' => 'WLF'
            ),
            'EH' => array(
                'en'   => 'Western Sahara',
                'es'   => 'Sáhara Occidental',
                'iso3' => 'ESH'
            ),
            'YE' => array(
                'en'   => 'Yemen',
                'es'   => 'Yemen',
                'iso3' => 'YEM'
            ),
            'ZM' => array(
                'en'   => 'Zambia',
                'es'   => 'Zambia',
                'iso3' => 'ZMB'
            ),
            'ZW' => array(
                'en'   => 'Zimbabwe',
                'es'   => 'Zimbabue',
                'iso3' => 'ZWE'
            )
        );
    }

    function languages() {
        return array(
            'en' => 'English',
            'es' => 'Spanish'
        );
    }

    function parseReturn($str) {
        $pairs = preg_split('/"\s/', $str);

        $result = array();
        foreach ($pairs as $string) {
            $string = str_replace('"', '', $string);

            parse_str($string, $data);

            $result = $result + $data;
        }

        return $result;
    }

    function getIp() {
        $ip = !empty($_SERVER['HTTP_CF_CONNECTING_IP']) ? htmlspecialchars((string)$_SERVER['HTTP_CF_CONNECTING_IP']) : false;
        if (!$ip) $ip = !empty($_SERVER['HTTP_CLIENT_IP']) ? htmlspecialchars((string)$_SERVER['HTTP_CLIENT_IP']) : false;

        if (!$ip) $ip = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? htmlspecialchars((string)$_SERVER['HTTP_X_FORWARDED_FOR']) : false;

        if (!$ip) $ip = !empty($_SERVER['REMOTE_ADDR']) ? htmlspecialchars((string)$_SERVER['REMOTE_ADDR']) : '0.0.0.0';

        // Hack because some of the IPs seems to be on the format: xxx.xxx.xxx.xxx, xxx.xxx.xxx.xxx
        $idx = strpos($ip, ',');
        if ($idx !== false) $ip = substr($ip, 0, $idx);

        return $ip;
    }

    function nth($number, $lang='en') {
        switch ($lang) {
            case 'es':
                return $number;

            default:
                $ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
                if (($number % 100) >= 11 && ($number % 100) <= 13)
                    return $number . 'th';

                return $number . $ends[$number % 10];
        }
    }

    function displayBook($major, $minor) {
        return strtoupper($major . ' / ' . $minor);
    }

    /**
     * Inserts an email address with link in a way which is not machine readable (stops spammers)
     *
     * @param string $email
     * @param string $linktext		(If different to email address)
     * @return string javascript
     *
     * @author Ben Peters
     *
     * created 2014-02-06
     * last modified 2014-02-06 BP
     */
    function obfuscateAddress($email, $linktext = "") {
        $email = str_replace("@", "*", $email);
        $obemail = "";
        for ($x = 0; $x < strlen($email); $x++) {
            $part = $email[$x];
            if (!empty($obemail)) $obemail .= '+';
            $obemail .= "'++" . $part . "++'";
        }

        if (empty($linktext))
            return '<script> addr=' . $obemail . ';addr=addr.replace(/\+\+/g,\'\');addr=addr.replace(/\*/g,\'@\');document.write(\'<a href="mailto:\'+addr+\'">\'+addr+\'</a>\');</script>';

        return '<script> addr=' . $obemail . ';addr=addr.replace(/\+\+/g,\'\');addr=addr.replace(/\*/g,\'@\');document.write(\'<a href="mailto:\'+addr+\'">' . $linktext . '</a>\');</script>';
    }

    function url_https($url) {
        return str_replace('http://', 'https://', site_url($url));
    }
    function clent_url_https($url) {
        return str_replace('http://', 'https://', SITE_URL.$url);
    }
    function _l() {
        $args = func_get_args();

        $CI =& get_instance();
        $line = call_user_func_array(array($CI->lang, 'line'), $args);

        // Hack to clear out remaining replacement things
        $line = preg_replace('/%\d+\$[a-z]/', '', $line);

        return trim($line);
    }

    function systemEmail($subject, $message = '') {
        $CI =& get_instance();

        $enabled = $CI->config->item('system_email');
        if (!$enabled)
            return;

        if ($message == '')
            $message = $subject;

        $CI->load->library('email');
        $CI->email->from($CI->config->item('admin_email'), $CI->config->item('site_name'));
        $CI->email->to($CI->config->item('admin_email'));
        $CI->email->subject($subject);
        $CI->email->message($message);
        $CI->email->send();
    }
    
    function getDiffTime($time) {
        $datetime1 = new DateTime($time);
        $datetime2 = new DateTime();
        $interval = $datetime1->diff($datetime2);
        $firstPosition = 'S';
        
        if($interval->y > 0) {
            $firstPosition = "Y";
        } else if($interval->m > 0) {
            $firstPosition = "M";
        } else if ($interval->d > 0) {
            $firstPosition = "D";
        } else if ($interval->h > 0) {
            $firstPosition = "H";
        } else if ($interval->i > 0) {
            $firstPosition = "Min";
        }
        
        if($firstPosition == "S"){
            $i = 1;
        } else {
            $i = 0;
        }
        
        $result = "";
        for (;$i < 2; $i++) {
            switch($firstPosition){
                case "Y":
                    if($interval->y == 1) {
                        $result .= $interval->y." year ";
                    } else {
                        $result .= $interval->y." years ";
                    }
                    $firstPosition = "M";
                    break;
                case "M":
                    if($interval->m == 1) {
                        $result .= $interval->m." month ";
                    } else {
                        $result .= $interval->m." months ";
                    }
                    $firstPosition = "D";
                    break;
                case "D":
                    if($interval->d == 1) {
                        $result .= $interval->d." day ";
                    } else {
                        $result .= $interval->d." days ";
                    }
                    $firstPosition = "H";
                    break;
                case "H":
                    if($interval->h == 1) {
                        $result .= $interval->h." hour ";
                    } else {
                        $result .= $interval->h." hours ";
                    }
                    $firstPosition = "Min";
                    break;
                case "Min":
                    if($interval->i == 1) {
                        $result .= $interval->i." minute ";
                    } else {
                        $result .= $interval->i." minutes ";
                    }
                    $firstPosition = "S";
                    break;
                case "S":
                    if($interval->s == 1) {
                        $result .= $interval->s." second ";
                    } else {
                        $result .= $interval->s." seconds ";
                    }
                    $firstPosition = "S";
                    break;
            }
        }
        
        return $result;
    }
    
    function getDateFormat($time) {
        $datetime = new DateTime($time);
        return $datetime->format('Y-m-d H:i:s');
    }
    
    function getStatus($status, $language = 'en') {
        switch($status){
            case "complete":
                return "completed";
            case "reject":
                return "rejected";
            case "approve":
                return "approved";
            default: 
                return $status;
        }
    }
    
    function getFutureContractType($type, $language = 'en') {
        switch($type){
            case "open_long":
                return "Open Long";
            case "close_short":
                return "Close Short";
            case "open_short":
                return "Open Short";
            case "close_long":
                return "Close Long";
            default: 
                return $type;
        }
    }
    
    function clearTicketStyle($tiket) { 
        return "<div".substr($tiket, stripos($tiket, ">"));
    }
    
    function getFormatFingerPrint($str) {
        return strtoupper(chunk_split($str, 4, ' '));
    }
    
    function get64FingerPrint($str) {
        $fingerprint = strtoupper(chunk_split($str, 4, ' '));
        return substr($fingerprint, count($fingerprint) - 21);
    }
    
    function adaptiveFutureOrderToUI($data) {
        $newData = array();
        foreach($data as $item) {
            $item->type = getFutureContractType($item->type);
            $item->_created = date('d/m/Y H:i:s', $item->_created / 1000);
            $item->typeContract = ucfirst($item->typeContract);
            if($item->avg){
                $item->avg = round($item->avg, getPrecision('cad'));
            } else {
                $item->avg = 0;
            }
            
            $item->margin = round($item->margin, getPrecision('btc'));
            $item->status = ucfirst($item->status);
            $newData[] = $item;
        }
        return $newData;
    }
    
    function adaptiveUIPosition($data) {
        $newData = array();
        foreach($data as $item) {
            $item->contract = ucfirst($item->contract);
            $item->type = ucfirst($item->type);
            $item->_created = date('d/m/Y H:i:s', $item->_created / 1000);
            $item->margin = round($item->margin, getPrecision('btc'));
            if($item->avg){
                $item->avg = round($item->avg, getPrecision('cad'));
                $item->pL = round($item->pL, getPrecision('btc'));
                $item->pLRatio = round($item->pLRatio, 2);
                $item->mCall = round($item->mCall, getPrecision('cad'));
            } else {
                $item->avg = 0;
            }
            $newData[] = $item;
        }
        return $newData;
    }