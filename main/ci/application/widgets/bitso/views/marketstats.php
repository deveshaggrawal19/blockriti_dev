<div class="row">
    <div class="col-sm-12">
        <div id="headline-stats">
            <div id="last-price">
                <div>
                    <div class="stat-desc"><?=_l('last_trade')?></div>
                    <?=displayCurrency($currencies[1], $lastPrice)?>
                </div>
            </div>
            <div id="main-stats">
                <table>
                    <tr>
                        <td>
                            <div class="stat-desc"><?=_l('24_hr_volume')?></div>
                            <?=displayCurrency($currencies[0], $volume,true)?>
                        </td>
                        <td>
                            <div class="stat-desc"><?=_l('daily_change')?></div>
                            <div class="dchange"><?=(($change<0)?'-':(($change>0)?'+':'')).displayCurrency($currencies[1], abs($change),true)?> (<?=$perc?>%)</div> <div class="<?=$data['arrow']?>"></div>
                        </td>
                        <td>
                            <div class="stat-desc"><?=_l('high')?></div>
                            <?=displayCurrency($currencies[1], $high)?>
                        </td>
                        <td>
                            <div class="stat-desc"><?=_l('low')?></div>
                            <?=displayCurrency($currencies[1], $low)?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="stat-desc"><?=_l('market_cap')?></div>

                        </td>
                        <td>
                            <?=displayCurrency($currencies[1], $market_cap, true)?>

                        </td>
                        <td>
                            <div class="stat-desc"><?=_l('total_xbt')?></div>

                        </td>
                        <td>
                            <?=displayCurrency($currencies[0], $total_xbt, true)?>

                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>