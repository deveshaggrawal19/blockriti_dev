<div class="col-xs-6 col-sm-3 last-trade">
    <strong>Last Trade</strong>
    <div class="amount"><?=displayCurrency($currencies[1], $lastPrice)?></div>
</div>

<div class="col-xs-6 col-sm-3 col">
    <div>
        <strong>Price Change</strong>
        <div class="amount"><?=displayCurrency($currencies[1], $change, true)?> / <?=$perc?>%</div>
    </div>
    <div>
        <strong>Market Cap</strong>
        <div class="amount"><?=displayCurrency($currencies[1], $market_cap, true)?></div>
    </div>
</div>

<div class="col-xs-6 col-sm-3 col">
    <div>
        <strong><?=_l('24_hr_volume')?></strong>
        <div class="amount"><?=displayCurrency($currencies[0], $volume, true)?></div>
    </div>
    <div>
        <strong>Total BTC</strong>
        <div class="amount"><?=displayCurrency($currencies[0], $total_xbt, true)?></div>
    </div>
</div>

<div class="col-xs-6 col-sm-3 col">
    <div>
        <strong><?=_l('high')?></strong>
        <div class="amount"><?=displayCurrency($currencies[1], $high)?></div>
    </div>
    <div>
        <strong><?=_l('low')?></strong>
        <div class="amount"><?=displayCurrency($currencies[1], $low)?></div>
    </div>
</div>