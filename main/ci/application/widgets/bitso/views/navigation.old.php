<? if ($user == 'guest' || $user->_status == 'authenticated'): ?>
    <a href="/login" class="btn btn-info navbar-btn navbar-right">
        <span class="glyphicon glyphicon-log-in"></span> <?=_l('login')?>
    </a>

    <a href="/register" class="btn btn-register navbar-btn navbar-right">
        <span class="glyphicon glyphicon-log-in"></span> <?=_l('register_now')?>
    </a>
<? else: ?>
    <?
        $types = array(
            'locked'             => _l('locked_for_orders'),
            'pending_deposit'    => _l('pending_funding'),
            'pending_withdrawal' => _l('pending_withdrawal'),
        );
    ?>
    <ul class="nav navbar-nav navbar-right" id="main-menu-top">

        <li id="stats-selector" class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <div class="balance-container">
                    <span class="balance"><?=displayCurrency($currencies[1], $lastPrice)?></span>
                    <br>
                    <span class="balance-desc"><?=_l('last_trade')?></span>
                </div>
                <b class="caret"></b>
            </a>
            <ul class="dropdown-menu">
                <li role="presentation" class="dropdown-header"><?=_l('volume')?></li>
                <li><a href="#"><?=displayCurrency($currencies[0], $volume)?></a></li>
                <li class="divider"></li>
                <li role="presentation" class="dropdown-header"><?=_l('high')?></li>
                <li><a href="#"><?=displayCurrency($currencies[1], $high)?></a></li>
                <li class="divider"></li>
                <li role="presentation" class="dropdown-header"><?=_l('low')?></li>
                <li><a href="#"><?=displayCurrency($currencies[1], $low)?></a></li>

            </ul>
        </li>


    <? foreach ($currencies as $currency): ?>
        <li id="session-balance-<?=$currency?>" class="dropdown img-<?=$currency?>">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <div class="balance-container">
                    <span class="balance"><?=displayCurrency($currency, $user->balances->{$currency . '_available'})?></span>
                    <br>
                    <span class="balance-desc"><?=code2Name($currency,$this->language)?></span>
                </div>
                <b class="caret"></b>
            </a>
            <ul class="dropdown-menu">
                <li><a href="/fund/<?=$currency?>"><?=_l('fund_account')?></a></li>
                <li><a href="/withdrawal/<?=$currency?>"><?=_l('withdraw')?></a></li>
            <? foreach ($types as $type=>$header): ?>
                <? $balance = $user->balances->{$currency . '_' . $type}; ?>
                <? if (bccomp($balance, '0', getPrecision($currency)) === 0) continue; ?>
                <li class="divider"></li>
                <li role="presentation" class="dropdown-header"><?=$header?></li>
                <li><a href="#"><span class="balance_locked"><?=displayCurrency($currency, $user->balances->{$currency . '_' . $type})?></span></a></li>
            <? endforeach; ?>
            </ul>
        </li>
    <? endforeach; ?>

        <li id="account-menu" class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><b class="caret"></b></a>
            <ul class="dropdown-menu">
                <li role="presentation" class="dropdown-header"><?=$user->first_name . ' ' . $user->last_name?></li>
                <li class="divider"></li>
                <li><?=anchor('/settings', _l('profile_settings'))?></li>
                <li><?=anchor('/history', _l('account_history'))?></li>
            <? if ($user->verified == 0): ?>
                <li><?=anchor('/verify', _l('verify'))?></li>
            <? else: ?>
                <li class="disabled"><a><?=_l('account_verified')?></a></li>
            <? endif; ?>
                <li class="divider"></li>
                <li><?=anchor('/api_setup', _l('api_setup'))?></li>
                <li class="divider"></li>
                <li><?=anchor('/logout', _l('log_out'), 'id="logout"')?></li>
            </ul>
        </li>
    </ul>

    <script type="text/javascript">
        $(document).ready(function(){
            $('#logout').on('click', function(){
                QCXUI.user.logout();
            });
        });
    </script>
<? endif; ?>