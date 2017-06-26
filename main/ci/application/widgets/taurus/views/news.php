<? foreach ($news as $item): ?>
<div class="news-item <?=$page == 'column' ? 'col-sm-12' : 'col-sm-4'?>">
    <div class="panel panel-form">
        <h3><?=$item->title?></h3>
        <?=$item->text?>
        <div class="date">
            <?=date('F j<\s\up>S</\s\up>, Y', $item->published / 1000)?>
        </div>
    </div>
</div>
<? endforeach; ?>