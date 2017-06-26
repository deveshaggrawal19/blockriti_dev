<div><?=$list?></div>
<ul>
    <li>Last trade: <strong><?=displayCurrency($currencies[1], $lastPrice)?></strong></li>
    <li>Volume: <strong><?=displayCurrency($currencies[0], $volume)?></strong></li>
    <li class="mHide">Low: <strong><?=displayCurrency($currencies[1], $low)?></strong></li>
    <li class="mHide">High: <strong><?=displayCurrency($currencies[1], $high)?></strong></li>
</ul>

<script type="text/javascript">
    $('#stats').on('click', 'a', function(e){
        e.preventDefault();
        $.ajax($(this).attr('href'), {
            dataType: 'html',
            success: function(data) {
                $('#stats').html(data);
            }
        });
    });
</script>