<div class="bitcoin_price">
    <div class="top_menu_mobile">
    Bitcoin price:
    <? foreach ($lastPrices as $currency=>$price): ?>
    <span class="balance last-<?=$currency?>"><?=displayCurrency($currency, $price)?></span>
    <? endforeach; ?>
    <div class="navblock_volume">
    Volume: 
    <span class="balance"><?=displayCurrency("btc", $volume)?></span>
    </div>
    </div>
    
    <div class="balance_mobile">
    Price:
    <? foreach ($lastPrices as $currency=>$price): ?>
    <span class="balance last-<?=$currency?>"><?=displayCurrency($currency, $price)?></span>
    <? endforeach; ?>
    <? if ($user == 'guest' || $user->_status == 'authenticated'): ?>
    | Volume: 
    <span class="balance"><?=displayCurrency("btc", $volume)?></span>
    <? else: ?>
        |
        Balance:
        <? foreach ($currencies as $currency): ?>
        <? $balance = $user->balances->{$currency . '_available'}; ?>
        <span class="balance last-<?=$currency?>"><?=displayCurrency($currency, $balance)?></span>
        <? endforeach; ?>
    <? endif; ?>
    </div>
</div>

<div class="dropdown dropdown_styling pull-right">
  <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-expanded="true">
    <i class="fa fa-life-ring"></i>
    <span class="caret"></span>
  </button>
  <ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="dropdownMenu1">
    <? if ($user == 'guest' || $user->_status == 'authenticated'): ?>
    <li role="presentation"><a role="menuitem" tabindex="-1" href="/login"> <?=_l('login')?></a></li>
    <li role="presentation"><a role="menuitem" tabindex="-1" href="/register">Register</a></li>
    
    <? else: ?>
    <li role="presentation " class="disabled"><a role="menuitem" tabindex="-1" href="#">Welcome, <?=$user->first_name?>!</a></li>
    <li role="presentation"><a role="menuitem" tabindex="-1" href="/logout">Logout</a></li>
    
    <? endif; ?>
    <li role="presentation"><a role="menuitem" tabindex="-1" href="/help">Help</a></li>
  </ul>
</div>

<? if ($user == 'guest' || $user->_status == 'authenticated'): ?>

<div class="top_menu_mobile">
<ul class="nav navbar-nav navbar-right">
    <li>
        <div class="cta">
            <a href="/login"><i class="fa fa-sign-in"></i> <?=_l('login')?></a>
            <a href="/register"><i class="fa fa-star-o"></i> Register</a>
            <a href="/help"><i class="fa fa-life-ring"></i> Help</a>
        </div>
    </li>
</ul>
</div>
<? else: ?>
<div class="top_menu_mobile">
<ul class="nav navbar-nav navbar-right">
    <li>
        <div class="cta">
            <span>Welcome, <?=$user->first_name?>!</span>
            <a href="/logout"><i class="fa fa-sign-out"></i> Logout</a>
            <a href="/help"><i class="fa fa-life-ring"></i> Help</a>
        </div>
    </li>
</ul>
</div>

<div class="top_menu_mobile">
<ul class="nav navbar-nav navbar-right balances">
    <li>Your Balances:</li>
    <? foreach ($currencies as $currency): ?>
        <? $balance = $user->balances->{$currency}; ?>
    <li class="balance _user_dynamic _balance_full_<?=$currency?>"><?=displayCurrency($currency, $balance)?></li>
    <? endforeach; ?>
</ul>
</div>
<? endif; ?>