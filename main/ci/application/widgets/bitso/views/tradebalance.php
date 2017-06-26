<div id="balances" class="">
<? foreach ($currencies as $currency): ?>
    <div class="tradebalance">
        <div class="balance-container">
            <span class="balance"><?=displayCurrency($currency, $user->balances->{$currency . '_available'})?></span>
            <br>
            <span class="balance-desc"><?=code2Name($currency,$this->language)?></span>
            <br>
            <? $balance = $user->balances->{$currency . '_locked'}; ?>
            <? if (bccomp($balance, '0', getPrecision($currency)) !== 0): ?>
            <div class="locked-balance">
                <span class="balance_locked"><?=displayCurrency($currency, $user->balances->{$currency . '_locked'})?></span><br>
                <span class="balance_locked_label"><?=_l('locked_for_orders')?></span>
            </div>
            <? endif; ?>
        </div>
        <ul class="fwbuttons">
            <li class="fund-<?=$currency?>">
                <div class="fwbutton"><a href="/fund/<?=$currency?>"></a> </div>
                <a href="/fund/<?=$currency?>"><?=_l('fund')?><br><?=makeSafeCurrency(strtoupper($currency))?></a>
            </li>
            <? $balance = $user->balances->{$currency.'_available'}; ?>
            <? if (bccomp($balance, '0', getPrecision($currency)) !== 0): ?>
            <li class="withdraw-<?=$currency?>">
                <div class="fwbutton"> <a href="/withdrawal/<?=$currency?>"></a> </div>
                <a href="/withdrawal/<?=$currency?>"><?=_l('withdraw')?><br><?=makeSafeCurrency(strtoupper($currency))?></a>
            </li>
            <? else: ?>
            <li class="withdraw-<?=$currency?> disabled">
                <div class="fwbutton"></div>
                <a href="#"><?=_l('withdraw')?><br><?=makeSafeCurrency(strtoupper($currency))?></a>
            </li>
            <? endif; ?>
        </ul>
    </div>
<? endforeach; ?>
</div>