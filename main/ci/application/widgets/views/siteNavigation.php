
<nav class="navbar navbar-default hidden-xs">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header adaptive_menu_block">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
        <a href="/" class="logo" style="padding-left: 15px;">
            <img src="<?=asset('images/logo.png')?>" />
        </a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse adaptive_menu_menu" id="bs-example-navbar-collapse-1" style="z-index: 1000;">
      <ul class="nav navbar-nav navbar-right">
            <li<?=$loc == 'home' ? ' class="active"' : ''?>><a href="/"><?=_l('menu_home')?></a></li>
            <li<?=$loc == 'dashboard' ? ' class="active"' : ''?>><a href="/account">Account</a></li>
            <li<?=$loc == 'trade' ? ' class="active"' : ''?>><a href="/trade"><?=_l('menu_trade')?></a></li>
            <li<?=$loc == 'orderbook' ? ' class="active"' : ''?>><a href="/trade/orderbook">Order Book</a></li>
            <li<?=$loc == 'deposit' ? ' class="active"' : ''?>><a href="/deposit">Deposit</a></li>
            <li<?=$loc == 'withdraw' ? ' class="active"' : ''?>><a href="/withdraw">Withdrawal</a></li>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>

<div class="visible-xs">
    <nav id="myNavmenu" class="navmenu navmenu-default navmenu-fixed-right offcanvas " role="navigation">
      <ul class="nav navmenu-nav">
        <li<?=$loc == 'home' ? ' class="active"' : ''?>><a href="/"><?=_l('menu_home')?></a></li>
        <li<?=$loc == 'dashboard' ? ' class="active"' : ''?>><a href="/account">Account</a></li>
        <li<?=$loc == 'trade' ? ' class="active"' : ''?>><a href="/trade"><?=_l('menu_trade')?></a></li>
        <li<?=$loc == 'orderbook' ? ' class="active"' : ''?>><a href="/trade/orderbook">Order Book</a></li>
        <li<?=$loc == 'deposit' ? ' class="active"' : ''?>><a href="/deposit">Deposit</a></li>
        <li<?=$loc == 'withdraw' ? ' class="active"' : ''?>><a href="/withdraw">Withdrawal</a></li>
      </ul>
    </nav>
    <div class="navbar navbar-default navbar-header adaptive_menu_block">
      <button type="button" class="navbar-toggle" data-toggle="offcanvas" data-target="#myNavmenu" data-canvas="body">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a href="/" class="logo" style="padding-left: 15px;">
            <img src="<?=asset('images/logo.png')?>" />
        </a>
    </div>
</div>

