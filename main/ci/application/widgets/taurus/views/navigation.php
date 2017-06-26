<ul class="nav navbar-nav balances">
    <li>Bitcoin prices:</li>
    <? foreach ($lastPrices as $currency=>$price): ?>
    <li class="balance"><?=displayCurrency($currency, $price)?></li>
    <? endforeach; ?>
</ul>

<? if ($user == 'guest' || $user->_status == 'authenticated'): ?>
<ul class="nav navbar-nav navbar-right">
    <li>
        <div class="cta">
            <a href="/login"><i class="fa fa-user"></i> <?=_l('login')?></a>
            <a href="/register"><i class="fa fa-star-o"></i> Register an Account</a>
            <a href="/help"><i class="fa fa-life-ring"></i> Help</a>
        </div>
    </li>
</ul>
<? else: ?>
<ul class="nav navbar-nav navbar-right">
    <li>
        <div class="cta">
            <span>Welcome <?=$user->first_name?></span>
            <a href="/logout"><i class="fa fa-user"></i> Logout</a>
            <a href="/help"><i class="fa fa-life-ring"></i> Help</a>
        </div>
    </li>
</ul>

<ul class="nav navbar-nav navbar-right balances">
    <li>Your Balances:</li>
    <? foreach ($currencies as $currency): ?>
        <? $balance = $user->balances->{$currency . '_available'}; ?>
    <li class="balance"><?=displayCurrency($currency, $balance)?></li>
    <? endforeach; ?>
</ul>
<? endif; ?>