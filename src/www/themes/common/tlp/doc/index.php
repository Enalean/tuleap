<?php

function discoverSection($basepath) {
    $discovery = array();
    $manifest_file = "$basepath/manifest.json";
    if (! is_file($manifest_file)) {
        return $discovery;
    }

    $content = file_get_contents($manifest_file);
    if (! $content) {
        return $discovery;
    }

    $manifest = json_decode($content, true);
    if (isset($manifest['label'])) {
        $discovery['label'] = $manifest['label'];
    }
    if (isset($manifest['shortcode'])) {
        $discovery['shortcode'] = $manifest['shortcode'];
    }
    if (isset($manifest['children'])) {
        foreach ($manifest['children'] as $child) {
            $section = discoverSection("$basepath/$child");
            if ($basepath === 'resources') {
                $discovery[$child] = $section;
            } else {
                $discovery['children'][$child] = $section;
            }
        }
    }

    return $discovery;
}

$sections = discoverSection('resources');
$current_section = key($sections);
if (isset($_GET['section']) && isset($sections[$_GET['section']])) {
    $current_section = $_GET['section'];
}
$sections[$current_section]['selected'] = true;

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tuleap UI Framework</title>

    <link rel="stylesheet" id="tlp-stylesheet" href="../dist/tlp-blue.min.css">

    <link rel="stylesheet" href="css/main.min.css">
    <link rel="stylesheet" href="codemirror/5.12.2/codemirror.css">
    <link rel="stylesheet" href="codemirror/5.12.2/addon/scroll/simplescrollbars.css">
    <link rel="stylesheet" href="codemirror/5.12.2/theme/mdn-like.css">

    <meta name="viewport" content="width=device-width,initial-scale=1.0">
</head>

<body class="blue">
<nav class="tlp-tabs main-nav">
    <div class="tlp-tab">
        <span id="doc-title">TLP</span> <i class="fa fa-dot-circle-o"></i>
        <nav class="tlp-tab-menu color-switcher">
            <a href="javascript:;" class="switch-to-orange"></a>
            <a href="javascript:;" class="switch-to-blue active"></a>
            <a href="javascript:;" class="switch-to-green"></a>
            <a href="javascript:;" class="switch-to-grey"></a>
            <a href="javascript:;" class="switch-to-purple"></a>
            <a href="javascript:;" class="switch-to-red"></a>
        </nav>
    </div>
    <?php
    foreach ($sections as $main => $section) {
        $selected_class = ($main === $current_section ? 'tlp-tab-active' : '');
        if (isset($section['children']) && count($section['children'])) {
            ?>
            <div class="tlp-tab <?= $selected_class; ?>">
                <?= $section['label'] ?> <i class="fa fa-caret-down"></i>
                <nav class="tlp-tab-menu">
                    <?php foreach ($section['children'] as $id => $subsection) : ?>
                        <a href="?section=<?= $main ?>#<?= $id ?>" class="tlp-tab-menu-item">
                            <?= $subsection['label'] ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
            <?php
        } else {
            ?>
            <a href="?section=<?= $main ?>" class="tlp-tab <?= $selected_class ?>">
                <?= $section['label'] ?>
            </a>
            <?php
        }
    }
    ?>
</nav>

<main class="tlp-framed doc-main">

    <?php
    if (isset($sections[$current_section]['children']) && count($sections[$current_section]['children'])) {
        $basepath            = 'resources/' . $current_section;
        $content             = $sections[$current_section]['children'];
        $may_have_header_doc = true;
    } else {
        $basepath            = 'resources/';
        $content             = array($current_section => $sections[$current_section]);
        $may_have_header_doc = false;
    }

    function displaySectionContent($documentation, $example, $demo, $section_id, $subsection_id = '')
    {
        if ($documentation) { ?>
            <div class="doc-information">
                <?= $documentation ?>
            </div>
        <?php }

        if ($example) { ?>
            <div class="demo">
                <div class="example" id="example-<?= "$section_id-$subsection_id" ?>"></div>
                <div class="code">
                    <textarea><?= htmlspecialchars($example) ?></textarea>
                </div>
            </div>
        <?php }

        if ($demo) { ?>
            <div class="demo">
                <?= $demo ?></textarea>
            </div>
        <?php }
    }

    if ($may_have_header_doc) {
        $example       = '';
        $demo          = '';
        $documentation = '';
        $doc_path      = "$basepath/doc.html";
        if (is_file($doc_path)) {
            $documentation = file_get_contents($doc_path);
        }

        if ($documentation) {
            ?>
            <section class="doc-section" id="<?= $current_section ?>">
                <h2 class="doc-section-title">
                    <?= $sections[$current_section]['label'] ?>
                    <?php if (isset($sections[$current_section]['shortcode']) && $sections[$current_section]['shortcode']) : ?>
                        <code class="code-inline"><?= $sections[$current_section]['shortcode'] ?></code>
                    <?php endif; ?>
                </h2>
                 <?php displaySectionContent($documentation, $example, $demo, ''); ?>
            </section>
            <?php
        }
    }

    foreach ($content as $id => $section) :
        $documentation = '';
        $doc_path      = "$basepath/$id/doc.html";
        if (is_file($doc_path)) {
            $documentation = file_get_contents($doc_path);
        }

        $example = '';
        $ex_path = "$basepath/$id/example.html";
        if (is_file($ex_path)) {
            $example = file_get_contents($ex_path);
        }

        $demo      = '';
        $demo_path = "$basepath/$id/demo.html";
        if (is_file($demo_path)) {
            $demo = file_get_contents($demo_path);
        }
        ?>
        <section class="doc-section" id="<?= $id ?>">
            <h2 class="doc-section-title">
                <?= $section['label'] ?>
                <?php if (isset($section['shortcode']) && $section['shortcode']) : ?>
                    <code class="code-inline"><?= $section['shortcode'] ?></code>
                <?php endif; ?>
            </h2>
            <?php
            displaySectionContent($documentation, $example, $demo, $id);

            if (isset($section['children']) && $section['children']) {
                foreach ($section['children'] as $subsection_id => $section) {
                    $documentation = '';
                    $doc_path      = "$basepath/$id/$subsection_id/doc.html";
                    if (is_file($doc_path)) {
                        $documentation = file_get_contents($doc_path);
                    }

                    $example = '';
                    $ex_path = "$basepath/$id/$subsection_id/example.html";
                    if (is_file($ex_path)) {
                        $example = file_get_contents($ex_path);
                    }

                    $demo      = '';
                    $demo_path = "$basepath/$id/$subsection_id/demo.html";
                    if (is_file($demo_path)) {
                        $demo = file_get_contents($demo_path);
                    }
                    ?>
                    <section class="doc-section" id="<?= $id ?>">
                        <?php if (isset($section['label'])) : ?>
                            <h3 class="doc-subsection-title">
                                <?= $section['label'] ?>
                                <?php if (isset($section['shortcode']) && $section['shortcode']) : ?>
                                    <code class="code-inline"><?= $section['shortcode'] ?></code>
                                <?php endif; ?>
                            </h3>
                        <?php endif;

                        displaySectionContent($documentation, $example, $demo, $id, $subsection_id);
                        ?>
                    </section>
                    <?php
                }
            }
            ?>
        </section>
    <?php endforeach; ?>
    <a href="#" title="Back to top" id="back-to-top"><i class="fa fa-arrow-up"></i></a>
</main>
<script type="text/javascript" src="../dist/tlp.en_US.min.js"></script>
<script type="text/javascript" src="js/polyfills.js"></script>
<script type="text/javascript" src="js/main.js"></script>
<script src="codemirror/5.12.2/codemirror-compressed.js"></script>
<script type="text/javascript" src="js/editors.js"></script>
</body>
