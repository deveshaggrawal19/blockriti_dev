<? foreach ($data as $d): ?>
<div class="alert <?=$d['class']?> alert-dismissable hidden-print">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <?=$d['message']?>
</div>
<? endforeach; ?>