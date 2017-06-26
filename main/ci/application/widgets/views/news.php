<? foreach ($news as $item): ?>
<?=$page == 'column' ? '<a name="' . $item->id . '"></a>' : ''?>
<div class="news-item <?=$page == 'column' ? 'col-sm-12' : 'col-sm-4'?>">
    <div class="panel panel-form">
        <h3><?=$item->title?></h3>
        <?=$page == 'column' ? $item->text : $item->excerpt?>
        <?=$page != 'column' ? '<br/><a href="/news#' . $item->id . '">Read more...</a>' : ''?>
        <div class="date">
            <?=date('F j<\s\up>S</\s\up>, Y', $item->published / 1000)?>
        </div>
    </div>
</div>
<? endforeach; ?>
<? if ($page == 'column'): ?>
<div class="row">
    <center>
    <? if ($prev > 0): ?>
    <a href="/news?page=<?=$prev?>"><span class="fa fa-chevron-circle-left"></span></a>
    <? endif; ?>
    <? if ($next > 0): ?>
    <a href="/news?page=<?=$next?>"><span class="fa fa-chevron-circle-right"></span></a>
    <? endif; ?>
<? endif; ?>
    </center>
</div>