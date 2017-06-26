<ul class="nav navbar-nav">
    <li>
        <a href="/" class="logo">
            <img src="<?=asset('images/t/logo.png')?>">
        </a>
    </li>
</ul>

<ul class="nav navbar-nav navbar-right">
    <li<?=$loc == 'home' ? ' class="active"' : ''?>><a href="/"><?=_l('menu_home')?></a></li>
    <li<?=$loc == 'dashboard' ? ' class="active"' : ''?>><a href="/account">Account</a></li>
    <li<?=$loc == 'trade' ? ' class="active"' : ''?>><a href="/trade"><?=_l('menu_trade')?></a></li>
    <li<?=$loc == 'orderbook' ? ' class="active"' : ''?>><a href="/trade/orderbook">Order Book</a></li>
    <li<?=$loc == 'deposit' ? ' class="active"' : ''?>><a href="/deposit">Deposit</a></li>
    <li<?=$loc == 'withdraw' ? ' class="active"' : ''?>><a href="/withdraw">Withdrawal</a></li>
</ul>