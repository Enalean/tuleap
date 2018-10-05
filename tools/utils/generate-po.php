#!/usr/bin/env php
<?php
#
# Copyright (c) Enalean, 2015 - 2018. All rights reserved
#
# This file is a part of Tuleap.
#
# Tuleap is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Tuleap is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Tuleap. If not, see <http://www.gnu.org/licenses/
#

use Tuleap\Language\Gettext\POTFileDumper;
use Tuleap\Templating\Mustache\DomainExtractor;
use Tuleap\Templating\Mustache\GettextCollector;
use Tuleap\Templating\Mustache\GettextExtractor;
use Tuleap\Templating\Mustache\GettextSectionContentTransformer;

require_once __DIR__ .'/../../src/vendor/autoload.php';

$basedir = $argv[1];

function info($message)
{
    echo "\033[32m$message\033[0m\n";
}

function warning($message)
{
    echo "\033[33m$message\033[0m\n";
}

function error($message)
{
    echo "\033[31m$message\033[0m\n";
}

function executeCommandAndExitIfStderrNotEmpty($command)
{
    $descriptorspec = array(
        0 => STDIN,
        1 => STDOUT,
        2 => array('pipe', 'wb')
    );

    $process = proc_open($command, $descriptorspec, $pipes);
    if (! is_resource($process)) {
        error("Can't execute command $command");
        exit(1);
    }

    $stderr       = stream_get_contents($pipes[2]);
    $return_value = proc_close($process);

    if (! empty($stderr)) {
        error($stderr);
        exit(1);
    }

    if ($return_value !== 0) {
        exit($return_value);
    }
}

$gettext_in_mustache_extractor = new DomainExtractor(
    new POTFileDumper(),
    new GettextExtractor(
        new Mustache_Parser(),
        new Mustache_Tokenizer(),
        new GettextCollector(new GettextSectionContentTransformer())
    )
);

info("[core] Generating .pot file");
$core_src = escapeshellarg("$basedir/src");
$template = escapeshellarg("$basedir/site-content/tuleap-core.pot");
executeCommandAndExitIfStderrNotEmpty("find $core_src -name '*.php' \
    | grep -v -E '(common/wiki/phpwiki|common/include/lib|vendor)' \
    | xargs xgettext \
        --default-domain=core \
        --from-code=UTF-8 \
        --no-location \
        --sort-output \
        --omit-header \
        -o - \
    | sed '/^msgctxt/d' \
    > $template");

info("[core] Ensure .pot strings uniquness");
executeCommandAndExitIfStderrNotEmpty("msguniq --sort-output --use-first -o $template $template");

info("[core] Generating .pot file for .mustache files");
$mustache_template = "$basedir/site-content/tuleap-core.mustache.pot";
$gettext_in_mustache_extractor->extract(
    'tuleap-core',
    "$basedir/src/templates",
    $mustache_template
);

info("[core] Combining .pot files into one");
executeCommandAndExitIfStderrNotEmpty("msgcat --sort-output -o $template $template ". escapeshellarg($mustache_template));
unlink($mustache_template);

info("[core] Merging .pot file into .po files");
$site_content = escapeshellarg("$basedir/site-content");
exec("find $site_content -name 'tuleap-core.po' -exec msgmerge --update \"{}\" $template \;");

info("[core][js] Generating default .pot file");
foreach (glob("$basedir/src/www/scripts/*", GLOB_ONLYDIR) as $path) {
    $manifest = "$path/build-manifest.json";
    if (!is_file($manifest)) {
        continue;
    }

    $json = json_decode(file_get_contents($manifest), true);
    if (isset($json['gettext-js']) && is_array($json['gettext-js'])) {
        foreach ($json['gettext-js'] as $component => $gettext) {
            info("[core][js][$component] Generating default .pot file");
            $src      = escapeshellarg("$path/${gettext['src']}");
            $po       = escapeshellarg("$path/${gettext['po']}");
            $template = escapeshellarg("$path/${gettext['po']}/template.pot");
            executeCommandAndExitIfStderrNotEmpty("find $src \
                        \( -name '*.js' -o -name '*.vue' \) \
                        -not \( -path '**/node_modules/*' -o -path '**/coverage/*' \) \
                    | xargs xgettext \
                        --language=JavaScript \
                        --default-domain=core \
                        --from-code=UTF-8 \
                        --no-location \
                        --sort-output \
                        --omit-header \
                        -o - \
                    | sed '/^msgctxt/d' \
                    > $template");

            info("[core][js][$component] Merging .pot file into .po files");
            exec("find $po -name '*.po' -exec msgmerge --update \"{}\" $template \;");
        }
    }

    info("[core][js] Merging $component .pot file into .po files");
    exec("find $path -name '*.po' -exec msgmerge --update \"{}\" $template \;");
}


foreach (glob("$basedir/plugins/*", GLOB_ONLYDIR) as $path) {
    $translated_plugin = basename($path);
    if (! is_file("$path/site-content/tuleap-$translated_plugin.pot")) {
        warning("[$translated_plugin] No .pot file found.");
        continue;
    }

    info("[$translated_plugin] Generating default .pot file");
    $src      = escapeshellarg("$path/include");
    $template = escapeshellarg("$path/site-content/tuleap-$translated_plugin.pot");
    $default  = escapeshellarg("$path/site-content/tuleap-$translated_plugin-default.pot");
    $plural   = escapeshellarg("$path/site-content/tuleap-$translated_plugin-plural.pot");
    $mustache = escapeshellarg("$path/site-content/tuleap-$translated_plugin-mustache.pot");
    executeCommandAndExitIfStderrNotEmpty("find $src -name '*.php' \
        | xargs xgettext \
            --keyword='dgettext:1c,2' \
            --default-domain=$translated_plugin \
            --from-code=UTF-8 \
            --omit-header \
            -o - \
        | msggrep \
            --msgctxt \
            --regexp='$translated_plugin\b' \
            - \
        | sed '/^msgctxt/d' \
        > $default");

    info("[$translated_plugin] Generating plural .pot file");
    executeCommandAndExitIfStderrNotEmpty("find $src -name '*.php' \
        | xargs xgettext \
            --keyword='dngettext:1c,2,3' \
            --default-domain=$translated_plugin \
            --from-code=UTF-8 \
            --omit-header \
            -o - \
        | msggrep \
            --msgctxt \
            --regexp='$translated_plugin\b' \
            - \
        | sed '/^msgctxt/d' \
        > $plural");

    info("[$translated_plugin] Generating .pot file for .mustache files");
    $gettext_in_mustache_extractor->extract(
        "tuleap-$translated_plugin",
        "$path/templates",
        "$path/site-content/tuleap-$translated_plugin-mustache.pot"
    );

    info("[$translated_plugin] Combining .pot files into one");
    exec("msgcat --no-location --sort-output --use-first $plural $default $mustache > $template");
    unlink("$path/site-content/tuleap-$translated_plugin-default.pot");
    unlink("$path/site-content/tuleap-$translated_plugin-plural.pot");
    unlink("$path/site-content/tuleap-$translated_plugin-mustache.pot");

    foreach (glob("$path/site-content/*", GLOB_ONLYDIR) as $foreign_dir) {
        if (basename($foreign_dir) === 'en_US') {
            continue;
        }

        $lc_messages = "$foreign_dir/LC_MESSAGES";
        if (! is_dir($lc_messages)) {
            $po_file = escapeshellarg("$lc_messages/tuleap-$translated_plugin.po");
            info("[$translated_plugin] Creating missing $po_file");
            mkdir($lc_messages, 0755, true);
            $content = <<<EOS
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"
EOS;

            file_put_contents($po_file, $content);
        }
    }

    info("[$translated_plugin] Merging .pot file into .po files");
    $site_content = escapeshellarg("$path/site-content");
    exec("find $site_content -name 'tuleap-$translated_plugin.po' -exec msgmerge --update \"{}\" $template \;");

    $manifest = "$path/build-manifest.json";
    if (is_file($manifest)) {
        $json = json_decode(file_get_contents($manifest), true);
        if (isset($json['gettext-js']) && is_array($json['gettext-js'])) {
            foreach ($json['gettext-js'] as $component => $gettext) {
                info("[$translated_plugin][js][$component] Generating default .pot file");
                $src      = escapeshellarg("$path/${gettext['src']}");
                $po       = escapeshellarg("$path/${gettext['po']}");
                $template = escapeshellarg("$path/${gettext['po']}/template.pot");

                executeCommandAndExitIfStderrNotEmpty("find $src \
                        \( -name '*.js' -o -name '*.vue' \) \
                        -not \( -path '**/node_modules/*' -o -path '**/coverage/*' \) \
                    | xargs xgettext \
                        --language=JavaScript \
                        --default-domain=core \
                        --from-code=UTF-8 \
                        --no-location \
                        --sort-output \
                        --omit-header \
                        -o - \
                    | sed '/^msgctxt/d' \
                    > $template");

                info("[$translated_plugin][js][$component] Merging .pot file into .po files");
                exec("find $po -name '*.po' -exec msgmerge --update \"{}\" $template \;");
            }
        }

        if (isset($json['gettext-vue']) && is_array($json['gettext-vue'])) {
            foreach ($json['gettext-vue'] as $component => $gettext) {
                info("[$translated_plugin][vue][$component] Generating default .pot file");
                $scripts           = escapeshellarg("$path/${gettext['scripts']}");
                $po                = escapeshellarg("$path/${gettext['po']}");
                $template          = escapeshellarg("$path/${gettext['po']}/template.pot");
                $vue_template_path = "$path/${gettext['po']}/template.pot";
                $vue_template      = escapeshellarg($vue_template_path);
                executeCommandAndExitIfStderrNotEmpty("(cd $scripts && npm run extract-gettext-cli -- --output $template)");

                exec("msgcat --no-location --sort-output -o $template $vue_template");

                info("[$translated_plugin][vue][$component] Merging .pot file into .po files");
                exec("find $po -name '*.po' -exec msgmerge --update \"{}\" $template \;");
            }
        }

        if (isset($json['gettext-smarty']) && is_array($json['gettext-smarty'])) {
            foreach ($json['gettext-smarty'] as $component => $gettext) {
                info("[$translated_plugin][smarty][$component] Generating default .pot file");
                $smarty_to_gettext   = escapeshellarg("$path/${gettext['smarty_to_gettext']}");
                $scripts_php         = escapeshellarg("$path/${gettext['scripts-php']}");
                $scripts_templates   = escapeshellarg("$path/${gettext['scripts-templates']}");
                $po                  = escapeshellarg("$path/${gettext['po']}");
                $template_php        = escapeshellarg("$path/${gettext['po']}/$component-php.pot");
                $template_php_plural = escapeshellarg("$path/${gettext['po']}/$component-php-plural.pot");
                $template_smarty     = escapeshellarg("$path/${gettext['po']}/$component-smarty.pot");
                $template            = escapeshellarg("$path/${gettext['po']}/$component.pot");
                $domain              = escapeshellarg($component);
                executeCommandAndExitIfStderrNotEmpty("(cd $scripts_templates && $smarty_to_gettext -d=$domain -o $template_smarty .)");
                executeCommandAndExitIfStderrNotEmpty("find $scripts_php -name '*.php' \
                    | xargs xgettext \
                        --keyword='dgettext:1c,2' \
                        --default-domain=$component \
                        --from-code=UTF-8 \
                        --omit-header \
                        -o - \
                    | msggrep \
                        --msgctxt \
                        --regexp='$component\b' \
                        - \
                    | sed '/^msgctxt/d' \
                    > $template_php");

                executeCommandAndExitIfStderrNotEmpty("find $scripts_php -name '*.php' \
                    | xargs xgettext \
                        --keyword='dngettext:1c,2,3' \
                        --default-domain=$component \
                        --from-code=UTF-8 \
                        --omit-header \
                        -o - \
                    | msggrep \
                        --msgctxt \
                        --regexp='$component\b' \
                        - \
                    | sed '/^msgctxt/d' \
                    > $template_php_plural");

                exec("msgcat --no-location --sort-output -o $template $template_php $template_php_plural $template_smarty");
                executeCommandAndExitIfStderrNotEmpty("rm $template_php");
                executeCommandAndExitIfStderrNotEmpty("rm $template_php_plural");
                executeCommandAndExitIfStderrNotEmpty("rm $template_smarty");

                info("[$translated_plugin][smarty][$component] Merging .pot file into .po files");
                exec("find $po -name '*.po' -exec msgmerge --update \"{}\" $template \;");
            }
        }
    }
}
