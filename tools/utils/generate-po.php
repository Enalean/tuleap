#!/usr/bin/env php
<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 *
 * SourceForge: Breaking Down the Barriers to Open Source Development
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

use Tuleap\Language\Gettext\POTFileDumper;
use Tuleap\Templating\Mustache\DomainExtractor;
use Tuleap\Templating\Mustache\GettextCollector;
use Tuleap\Templating\Mustache\GettextExtractor;
use Tuleap\Templating\Mustache\GettextSectionContentTransformer;

require_once __DIR__ . '/../../src/vendor/autoload.php';

$basedir = $argv[1];
$plugin  = $argv[2] ?: '';

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
    $descriptorspec = [
        0 => STDIN,
        1 => STDOUT,
        2 => ['pipe', 'wb'],
    ];

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

if (! $plugin) {
    info("[core] Generating .pot file");
    $core_src = escapeshellarg("$basedir/src");
    $template = escapeshellarg("$basedir/site-content/tuleap-core.pot");
    executeCommandAndExitIfStderrNotEmpty(
        "find $core_src -name '*.php' \
    | grep -v -E '(common/wiki/phpwiki|common/include/lib|vendor)' \
    | xargs xgettext \
        --default-domain=core \
        --from-code=UTF-8 \
        --no-location \
        --sort-output \
        --omit-header \
        -o - \
    | sed '/^msgctxt/d' \
    > $template"
    );

    info("[core] Ensure .pot strings uniquness");
    executeCommandAndExitIfStderrNotEmpty("msguniq --sort-output --use-first -o $template $template");

    info("[core] Generating .pot file for .mustache files");
    $mustache_template = "$basedir/site-content/tuleap-core.mustache.pot";
    $gettext_in_mustache_extractor->extract(
        'tuleap-core',
        [
            "$basedir/src/templates",
            "$basedir/src/themes/BurningParrot/templates",
            "$basedir/src/themes/FlamingParrot/templates",
            "$basedir/src/common/FRS",
            "$basedir/src/common/User",
        ],
        $mustache_template
    );

    info("[core] Combining .pot files into one");
    executeCommandAndExitIfStderrNotEmpty(
        "msgcat --sort-output -o $template $template " . escapeshellarg($mustache_template)
    );
    unlink($mustache_template);

    info("[core] Merging .pot file into .po files");
    $site_content = escapeshellarg("$basedir/site-content");
    exec("find $site_content -name 'tuleap-core.po' -exec msgmerge --update \"{}\" $template \; -exec msgattrib --no-obsolete --clear-fuzzy --empty -o \"{}\" \"{}\" \;");

    $core_manifest = "$basedir/build-manifest.json";
    $json          = json_decode(file_get_contents($core_manifest), true);

    gettextTS("core", $basedir, $json);
    gettextVue("core", $basedir, $json);
    gettextVue3("core", $basedir, $json);
}

foreach (glob("$basedir/plugins/*", GLOB_ONLYDIR) as $path) {
    $translated_plugin = basename($path);
    if ($plugin && $translated_plugin !== $plugin) {
        continue;
    }

    gettextPHP($path, $translated_plugin, $gettext_in_mustache_extractor);

    $manifest = "$path/build-manifest.json";
    if (is_file($manifest)) {
        $json = json_decode(file_get_contents($manifest), true);
        gettextTS($translated_plugin, $path, $json);
        gettextSmarty($translated_plugin, $path, $json);
        gettextVue($translated_plugin, $path, $json);
        gettextVue3($translated_plugin, $path, $json);
        gettextAngularJS($translated_plugin, $path, $json);
    }
}

/**
 * @param                 $path
 */
function gettextPHP($path, string $translated_plugin, DomainExtractor $gettext_in_mustache_extractor): void
{
    info("[$translated_plugin] Generating default .pot file");
    $src      = escapeshellarg("$path/include");
    $template = escapeshellarg("$path/site-content/tuleap-$translated_plugin.pot");
    $default  = escapeshellarg("$path/site-content/tuleap-$translated_plugin-default.pot");
    $plural   = escapeshellarg("$path/site-content/tuleap-$translated_plugin-plural.pot");
    $mustache = escapeshellarg("$path/site-content/tuleap-$translated_plugin-mustache.pot");
    executeCommandAndExitIfStderrNotEmpty(
        "find $src -name '*.php' \
        | xargs xgettext \
            --keyword='dgettext:1c,2' \
            --default-domain=$translated_plugin \
            --from-code=UTF-8 \
            --omit-header \
            -o - \
        | msguniq --sort-output --use-first -o - \
        | msggrep \
            --msgctxt \
            --regexp='$translated_plugin\b' \
            - \
        | sed '/^msgctxt/d' \
        > $default"
    );

    info("[$translated_plugin] Generating plural .pot file");
    executeCommandAndExitIfStderrNotEmpty(
        "find $src -name '*.php' \
        | xargs xgettext \
            --keyword='dngettext:1c,2,3' \
            --default-domain=$translated_plugin \
            --from-code=UTF-8 \
            --omit-header \
            -o - \
        | msguniq --sort-output --use-first -o - \
        | msggrep \
            --msgctxt \
            --regexp='$translated_plugin\b' \
            - \
        | sed '/^msgctxt/d' \
        > $plural"
    );

    info("[$translated_plugin] Generating .pot file for .mustache files");
    $gettext_in_mustache_extractor->extract(
        "tuleap-$translated_plugin",
        ["$path/templates", "$path/include"],
        "$path/site-content/tuleap-$translated_plugin-mustache.pot"
    );

    info("[$translated_plugin] Combining .pot files into one");

    executeCommandAndExitIfStderrNotEmpty("msgcat --no-location --sort-output --use-first $plural $default $mustache > $template");
    unlink("$path/site-content/tuleap-$translated_plugin-default.pot");
    unlink("$path/site-content/tuleap-$translated_plugin-plural.pot");
    unlink("$path/site-content/tuleap-$translated_plugin-mustache.pot");
    if (filesize("$path/site-content/tuleap-$translated_plugin.pot") === 0) {
        info("[$translated_plugin] Nothing to translate in gettext PHP");
        return;
    }

    foreach (glob("$path/site-content/*", GLOB_ONLYDIR) as $foreign_dir) {
        if (basename($foreign_dir) === 'en_US') {
            continue;
        }

        $lc_messages = "$foreign_dir/LC_MESSAGES";
        if (! is_dir($lc_messages)) {
            info("[$translated_plugin] Creating LC_MESSAGES folder $lc_messages");
            if (! mkdir($lc_messages, 0755, true) && ! is_dir($lc_messages)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $lc_messages));
            }
        }

        $po_file = "$lc_messages/tuleap-$translated_plugin.po";
        if (! is_file($po_file)) {
            info("[$translated_plugin] Creating $po_file");
            $content = <<<'EOS'
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"

EOS;

            file_put_contents($po_file, $content);
        }
    }

    info("[$translated_plugin] Merging .pot file into .po files");
    $site_content = escapeshellarg("$path/site-content");
    exec("find $site_content -name 'tuleap-$translated_plugin.po' -exec msgmerge --update \"{}\" $template \; -exec msgattrib --no-obsolete --clear-fuzzy --empty -o \"{}\" \"{}\" \;");
}

function gettextAngularJS(string $translated_plugin, string $path, array $manifest_json): void
{
    if (! isset($manifest_json['gettext-angularjs']) || ! is_array($manifest_json['gettext-angularjs'])) {
        return;
    }

    foreach ($manifest_json['gettext-angularjs'] as $component => $gettext) {
        $gettext_step_header = sprintf("[%s][angularjs][%s]", $translated_plugin, $component);
        info("$gettext_step_header Generating default .pot file");
        $src      = escapeshellarg("$path/{$gettext['src']}");
        $po       = escapeshellarg("$path/{$gettext['po']}");
        $template = escapeshellarg("$path/{$gettext['po']}/template.pot");

        executeCommandAndExitIfStderrNotEmpty("node_modules/.bin/angular-gettext-cli --files '$src/**/*.+(js|html|ts)' --exclude '**/*.+(test.js|test.ts|d.ts)' --dest $template");
        executeCommandAndExitIfStderrNotEmpty("msgcat --no-location --sort-output -o $template $template");

        info("$gettext_step_header Merging .pot file into .po files");
        exec("find $po -name '*.po' -exec msgmerge --update \"{}\" $template \; -exec msgattrib --no-obsolete --clear-fuzzy --empty -o \"{}\" \"{}\" \;");
    }
}

function gettextVue($translated_plugin, $path, $manifest_json)
{
    if (! isset($manifest_json['gettext-vue']) || ! is_array($manifest_json['gettext-vue'])) {
        return;
    }

    foreach ($manifest_json['gettext-vue'] as $component => $gettext) {
        info("[$translated_plugin][vue][$component] Generating default .pot file");
        $src      = escapeshellarg("$path/{$gettext['src']}");
        $po       = escapeshellarg("$path/{$gettext['po']}");
        $template = escapeshellarg("$path/{$gettext['po']}/template.pot");

        executeCommandAndExitIfStderrNotEmpty("tools/utils/scripts/vue-typescript-gettext-extractor-cli.js \
        $(find $src  \
            -not \( -name '*.cy.ts' -o -name '*.test.js' -o -name '*.test.ts' -o -name '*.d.ts' \) \
            -not \( -path '**/node_modules/*' -o -path '**/coverage/*' \) \
            -and \( -type f -name '*.vue' -o -name '*.ts' -o -name '*.js' \) \
        ) \
        --output $template");
        executeCommandAndExitIfStderrNotEmpty("msguniq --sort-output --use-first -o $template $template");

        executeCommandAndExitIfStderrNotEmpty("msgcat --no-location --sort-output -o $template $template");

        info("[$translated_plugin][vue][$component] Merging .pot file into .po files");
        exec("find $po -name '*.po' -exec msgmerge --update \"{}\" $template \; -exec msgattrib --no-obsolete --clear-fuzzy --empty -o \"{}\" \"{}\" \;");
    }
}

function gettextVue3(string $translated_plugin, string $path, array $manifest_json): void
{
    if (! isset($manifest_json['gettext-vue3']) || ! is_array($manifest_json['gettext-vue3'])) {
        return;
    }

    foreach ($manifest_json['gettext-vue3'] as $component => $gettext) {
        info("[$translated_plugin][vue][$component] Generating default .pot file");
        $src      = escapeshellarg("$path/{$gettext['src']}");
        $po       = escapeshellarg("$path/{$gettext['po']}");
        $template = escapeshellarg("$path/{$gettext['po']}/template.pot");

        executeCommandAndExitIfStderrNotEmpty("tools/utils/scripts/vue3-typescript-gettext-extractor-cli.js \
        $(find $src  \
            -not \( -name '*.cy.ts' -o -name '*.test.js' -o -name '*.test.ts' -o -name '*.d.ts' \) \
            -not \( -path '**/node_modules/*' -o -path '**/coverage/*' \) \
            -and \( -type f -name '*.vue' -o -name '*.ts' -o -name '*.js' \) \
        ) \
        --output $template");
        executeCommandAndExitIfStderrNotEmpty("msguniq --sort-output --use-first -o $template $template");

        executeCommandAndExitIfStderrNotEmpty("msgcat --no-location --sort-output -o $template $template");

        info("[$translated_plugin][vue][$component] Merging .pot file into .po files");
        exec("find $po -name '*.po' -exec msgmerge --update \"{}\" $template \; -exec msgattrib --no-obsolete --clear-fuzzy --empty -o \"{}\" \"{}\" \;");
    }
}

function gettextTS($translated_plugin, $path, $manifest_json)
{
    if (! isset($manifest_json['gettext-ts']) || ! is_array($manifest_json['gettext-ts'])) {
        return;
    }

    foreach ($manifest_json['gettext-ts'] as $component => $gettext) {
        info("[$translated_plugin][ts][$component] Generating default .pot file");
        $src      = escapeshellarg("$path/{$gettext['src']}");
        $po       = escapeshellarg("$path/{$gettext['po']}");
        $template = escapeshellarg("$path/{$gettext['po']}/template.pot");

        executeCommandAndExitIfStderrNotEmpty("tools/utils/scripts/vue3-typescript-gettext-extractor-cli.js \
        $(find $src \
            -not \( -name '*.cy.ts' -o -name '*.test.js' -o -name '*.test.ts' -o -name '*.d.ts' \) \
            -not \( -path '**/node_modules/*' -o -path '**/coverage/*' \) \
            -and \( -type f -name '*.ts' -o -name '*.js' \) \
        ) \
        --output $template");
        executeCommandAndExitIfStderrNotEmpty("msguniq --sort-output --use-first -o $template $template");

        executeCommandAndExitIfStderrNotEmpty("msgcat --no-location --sort-output -o $template $template");

        info("[$translated_plugin][ts][$component] Merging .pot file into .po files");
        exec("find $po -name '*.po' -exec msgmerge --update \"{}\" $template \; -exec msgattrib --no-obsolete --clear-fuzzy --empty -o \"{}\" \"{}\" \;");
    }
}

function gettextSmarty($translated_plugin, $path, $manifest_json)
{
    if (! isset($manifest_json['gettext-smarty']) || ! is_array($manifest_json['gettext-smarty'])) {
        return;
    }

    foreach ($manifest_json['gettext-smarty'] as $component => $gettext) {
        info("[$translated_plugin][smarty][$component] Generating default .pot file");
        $smarty_to_gettext   = escapeshellarg("$path/{$gettext['smarty_to_gettext']}");
        $scripts_php         = escapeshellarg("$path/{$gettext['scripts-php']}");
        $scripts_templates   = escapeshellarg("$path/{$gettext['scripts-templates']}");
        $po                  = escapeshellarg("$path/{$gettext['po']}");
        $template_php        = escapeshellarg("$path/{$gettext['po']}/$component-php.pot");
        $template_php_plural = escapeshellarg("$path/{$gettext['po']}/$component-php-plural.pot");
        $template_smarty     = escapeshellarg("$path/{$gettext['po']}/$component-smarty.pot");
        $template            = escapeshellarg("$path/{$gettext['po']}/$component.pot");
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
        exec("find $po -name '*.po' -exec msgmerge --update \"{}\" $template \; -exec msgattrib --no-obsolete --clear-fuzzy --empty -o \"{}\" \"{}\" \;");
    }
}
