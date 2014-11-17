Tuleap <?= $release->version ?> (<?= $release->date ?>)
========================================

<?php foreach ($release->sections as $section): ?>
<?= $section->label ?>

-----------------------

<?php include 'changes.php'; ?>

<?php foreach ($section->sections as $section): ?>
### <?= $section->label ?> <?= $section->version ?>


<?php include 'changes.php'; ?>

<?php endforeach; ?>
<?php endforeach; ?>
