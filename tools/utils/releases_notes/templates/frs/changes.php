<?php if ($section->changes): ?>
<?php foreach ($section->changes as $change): ?>
    * <?= $change ?>

<?php endforeach; ?>
<?php endif; ?>
