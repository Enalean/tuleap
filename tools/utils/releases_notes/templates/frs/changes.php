<? if ($section->changes): ?>
<? foreach ($section->changes as $change): ?>
    * <?= $change ?>

<? endforeach; ?>
<? endif; ?>
