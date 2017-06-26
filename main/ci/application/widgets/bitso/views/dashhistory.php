<div id="dashhistory">
    <table>
    <tr>
        <th width="12%"></th>
        <th width="18%">Date</th>
        <th width="70%">Action</th>
    </tr>
    <? foreach ($events as $date=>$entry): ?>
    <tr class="dash-history-<?=strtolower($entry->etype.(isset($entry->type)?$entry->type:''))?>">
        <td><div class="indic"></div><div class="hicon"></div></td>
        <td><?=date('d/m/y H:i:s', explode("-",$date)[0] / 1000)?></td>
        <td>
        <?
        switch($entry->etype) {
            case 'T':
                switch ($entry->type) {
                    case 'sell':
                        //$fee   = bccomp($entry->fee, '0', getPrecision($entry->minor_currency)) > 0 ? displayCurrency($entry->minor_currency, $entry->fee) : '--';
                        $total = displayCurrency($entry->minor_currency, $entry->total);
                        echo _l('you_sold') . ' ' . displayCurrency($entry->major_currency, $entry->amount) . ' ' . _l('for') . ' ' . displayCurrency($entry->minor_currency, $entry->value);
                        break;

                    case 'buy':
                        //$fee = bccomp($entry->fee, '0', getPrecision($entry->major_currency)) > 0 ? displayCurrency($entry->major_currency, $entry->fee) : '--';
                        $total = displayCurrency($entry->major_currency, $entry->total) ;
                        echo _l('you_bought') . ' ' . displayCurrency($entry->major_currency, $entry->amount)  . ' ' . _l('for') . ' ' . displayCurrency($entry->minor_currency, $entry->value);
                        break;
                }
                break;

            case 'RW':
                echo _l('you_sent') . ' ' . displayCurrency($entry->currency, $entry->amount) . ' ' . _l('to_the_ripple_network');
                break;

            case 'RD':
                echo _l('you_redeemed') . ' ' . displayCurrency($entry->currency, $entry->amount) . ' ' . _l('from_the_ripple_network');
                break;

            case 'D':
            case 'BD':
                echo _l('you_funded_your_account_with') . ' ' . displayCurrency($entry->currency, $entry->amount) . ' via ' . code2Name($entry->method);
                break;

            case 'W':
            case 'BW':
                echo _l('you_withdrew') . ' ' . displayCurrency($entry->currency, $entry->amount) . ' ' . _l('from_your_account') .' via '. code2Name($entry->method);
                break;

            case 'R':
                echo _l('you_got_a_referral_fee_from') . ' ' . $entry->name . ' ' . _l('of') . ' '.displayCurrency($entry->currency, $entry->amount);
                break;
        }
        ?>
        </td>
    </tr>
    <? endforeach ?>
    </table>
</div>