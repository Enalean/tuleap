<h1>Tuleap <?= $release->version ?> - Release Notes</h1>
<em><?= $release->date ?></em>
<? foreach ($release->sections as $section): ?>
    <h2><?= $section->label ?></h2>
    <? include 'changes.php'; ?>
    <? foreach ($section->sections as $section): ?>
        <h3><?= $section->label ?></h3>
        <? include 'changes.php'; ?>

    <? endforeach; ?>

<? endforeach; ?>
