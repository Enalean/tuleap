<?php if ($section->changes): ?>
    <ul>
        <?php foreach ($section->changes as $change): ?>
        <li><?= nl2br($change) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

