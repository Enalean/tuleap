<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__ . '/../include/pre.php';

use Tuleap\Layout\IncludeAssets;

/**
 * @psalm-return list<mixed>
 */
function discoverSection(string $basepath): array
{
    $discovery     = [];
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

$sections        = discoverSection('resources');
$current_section = key($sections);
if (isset($_GET['section'], $sections[$_GET['section']])) {
    $current_section = key(array_intersect_key($sections, [$_GET['section'] => true]));
}
$sections[$current_section]['selected'] = true;

$include_asset_framework = new IncludeAssets(__DIR__ . '/../../scripts/tlp/frontend-assets', '/assets/core/tlp');
$tlp_script_url          = $include_asset_framework->getFileURL('tlp-en_US.js');
$tlp_blue_css            = $include_asset_framework->getFileURL('tlp.css');
$tlp_vars_blue_css       = $include_asset_framework->getFileURL('tlp-vars-blue.css');

$include_asset_doc   = new IncludeAssets(__DIR__ . '/dist', 'dist');
$main_doc_stylesheet = $include_asset_doc->getFileURL('style.css');
$main_doc_script     = $include_asset_doc->getFileURL('script.js');

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>TLP</title>

    <link rel="stylesheet" id="tlp-stylesheet" href="<?php echo $tlp_blue_css ?>">
    <link rel="stylesheet" id="tlp-stylesheet-vars" href="<?php echo $tlp_vars_blue_css ?>">

    <link rel="stylesheet" href="<?php echo $main_doc_stylesheet ?>">

    <meta name="viewport" content="width=device-width,initial-scale=1.0">
</head>

<body class="blue">
<nav class="tlp-tabs main-nav">
    <span id="doc-title">TLP</span>
    <?php
    foreach ($sections as $main => $section) {
        $selected_class = ($main === $current_section ? 'tlp-tab-active' : '');
        if (isset($section['children']) && count($section['children'])) {
            ?>
            <div class="tlp-tab <?php echo $selected_class; ?>">
                <?php echo $section['label'] ?> <i class="fa-solid fa-caret-down"></i>
                <nav class="tlp-tab-menu">
                    <?php foreach ($section['children'] as $id => $subsection) : ?>
                        <a href="?section=<?php echo $main ?>#<?php echo $id ?>" class="tlp-tab-menu-item">
                            <?php echo $subsection['label'] ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
            <?php
        } else {
            ?>
            <a href="?section=<?php echo $main ?>" class="tlp-tab <?php echo $selected_class ?>">
                <?php echo $section['label'] ?>
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
        $content             = [$current_section => $sections[$current_section]];
        $may_have_header_doc = false;
    }

    function displaySectionContent(
        string $documentation,
        string $example,
        string $demo,
        string $section_id,
        string $subsection_id = '',
    ): void {
        if ($documentation) { ?>
            <div class="doc-information">
                <?php echo $documentation ?>
            </div>
        <?php }

        if ($example) { ?>
            <div class="demo">
                <div class="example" id="example-<?php echo "$section_id-$subsection_id" ?>"></div>
                <div class="code">
                    <textarea><?php echo htmlspecialchars($example) ?></textarea>
                </div>
            </div>
        <?php }

        if ($demo) { ?>
            <div class="demo">
                <?php echo $demo ?></textarea>
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
            <section class="doc-section" id="<?php echo $current_section ?>">
                <h2 class="doc-section-title">
                    <?php echo $sections[$current_section]['label'] ?>
                    <?php if (isset($sections[$current_section]['shortcode']) && $sections[$current_section]['shortcode']) : ?>
                        <code class="code-inline"><?php echo $sections[$current_section]['shortcode'] ?></code>
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
        <section class="doc-section" id="<?php echo $id ?>">
            <h2 class="doc-section-title">
                <?php echo $section['label'] ?>
                <?php if (isset($section['shortcode']) && $section['shortcode']) : ?>
                    <code class="code-inline"><?php echo $section['shortcode'] ?></code>
                <?php endif; ?>
            </h2>
            <?php
            displaySectionContent($documentation, $example, $demo, (string) $id);

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
                    <section class="doc-section" id="<?php echo $id ?>">
                        <?php if (isset($section['label'])) : ?>
                            <h3 class="doc-subsection-title">
                                <?php echo $section['label'] ?>
                                <?php if (isset($section['shortcode']) && $section['shortcode']) : ?>
                                    <code class="code-inline"><?php echo $section['shortcode'] ?></code>
                                <?php endif; ?>
                            </h3>
                        <?php endif;

                        displaySectionContent($documentation, $example, $demo, (string) $id, $subsection_id);
                        ?>
                    </section>
                    <?php
                }
            }
            ?>
        </section>
    <?php endforeach; ?>
    <section class="doc-appearance">
        <div class="color-switcher">
            Theme color:
            <a href="javascript:;" class="switch-to-orange" title="Orange"></a>
            <a href="javascript:;" class="switch-to-blue active" title="Blue"></a>
            <a href="javascript:;" class="switch-to-green" title="Green"></a>
            <a href="javascript:;" class="switch-to-grey" title="Grey"></a>
            <a href="javascript:;" class="switch-to-purple" title="Purple"></a>
            <a href="javascript:;" class="switch-to-red" title="Red"></a>
        </div>
        <div class="background-color-switcher">
            Background:
            <a href="javascript:;" class="switch-to-background-white" title="White"></a>
            <a href="javascript:;" class="switch-to-background-grey active" title="Grey"></a>
            <a href="javascript:;" class="switch-to-background-dark" title="Dark"></a>
        </div>
    </section>
</main>
<script type="text/javascript" src="<?php echo $tlp_script_url; ?>"></script>
<script type="text/javascript">
    window.manifest_framework_file = <?php
        echo (string) file_get_contents(__DIR__ . '/../../scripts/tlp/frontend-assets/manifest.json');
    ?>
</script>
<script type="text/javascript" src="<?php echo $main_doc_script; ?>"></script>
</body>
