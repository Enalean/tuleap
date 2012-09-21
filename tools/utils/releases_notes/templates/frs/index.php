Tuleap <?= $release->version ?> (<?= $release->date ?>)
========================================

<? foreach ($release->sections as $section): ?>
<?= $section->label ?>

-----------------------

<? include 'changes.php'; ?>

<? foreach ($section->sections as $section): ?>
### <?= $section->label ?> <?= $section->version ?>


<? include 'changes.php'; ?>

<? endforeach; ?>
<? endforeach; ?>
