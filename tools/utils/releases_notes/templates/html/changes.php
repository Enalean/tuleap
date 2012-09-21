<? if ($section->changes): ?>
    <ul>
        <? foreach ($section->changes as $change): ?>
        <li><?= nl2br($change) ?></li>
        <? endforeach; ?>
    </ul>
<? endif; ?>

