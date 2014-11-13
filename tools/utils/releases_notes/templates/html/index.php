<h1>Tuleap <?= $release->version ?> - Release Notes</h1>
<em><?= $release->date ?></em>
<?php foreach ($release->sections as $section): ?>
    <h2><?= $section->label ?></h2>
    <?php include 'changes.php'; ?>
    <?php foreach ($section->sections as $section): ?>
        <h3><?= $section->label ?></h3>
        <?php include 'changes.php'; ?>

    <?php endforeach; ?>

<?php endforeach; ?>
