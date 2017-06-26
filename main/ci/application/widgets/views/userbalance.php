<table>
    <? $total = 0 ?>
    <? foreach ($types as $type): ?>
        <? $balance = $user->balances->{$currency . '_' . $type}; ?>
        <? $total += $balance; ?>
        <tr>
            <td><?=_l($type)?></td>
            <td <?=((bccomp($balance, '0', getPrecision($currency)) === 0) ? 'class="zerobal"':''); ?>><?=displayCurrency($currency, $user->balances->{$currency . '_' . $type})?></td>
        </tr>
    <? endforeach; ?>
    <? if ($total!==$user->balances->{$currency . '_available'}): ?>
        <tr>
            <td><?=_l('total')?></td>
            <td><?=displayCurrency($currency, $total)?></td>
        </tr>
    <? endif; ?>
</table>
