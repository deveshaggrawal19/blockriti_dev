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

        <li>
            <div class="balance-container">
                <span class="balance"><?=displayCurrency($currencies[1], $lastPrice)?></span>
                <br>
                <span class="balance-desc"><?=_l('last_trade')?></span>
            </div>
        </li>
        <li>
            <div class="balance-container">
                <span class="balance"><?=displayCurrency($currencies[1], $high)?></span>
                <br>
                <span class="balance-desc"><?=_l('high')?></span>
            </div>
        </li>
        <li>
            <div class="balance-container">
                <span class="balance"><?=displayCurrency($currencies[1], $low)?></span>
                <br>
                <span class="balance-desc"><?=_l('low')?></span>
            </div>
        </li>
        <li>
            <div class="balance-container">
                <span class="balance"><?=displayCurrency($currencies[0], $volume)?></span>
                <br>
                <span class="balance-desc"><?=_l('volume')?></span>
            </div>
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