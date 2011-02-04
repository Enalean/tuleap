<?php

require_once 'ReleaseVersionComparator.php';

$rootdir = realpath(dirname(__FILE__).'/../..');
chdir($rootdir);

$svnServer = 'https://codex.cro.st.com/svnroot/codex-cc';
$tagBase   = '/contrib/st/intg/Codendi-ST-4.0/tags';

// Get last tag
$tags = simplexml_load_string(shell_exec('svn ls --xml '.$svnServer.$tagBase));
$max  = 0;
$maxEntry = null;
foreach ($tags->list->entry as $entry) {
    if (isset($maxEntry)) {
        if (version_compare($entry->name, $maxEntry->name, '>')) {
            $maxEntry = $entry;
        }
    } else {
       $maxEntry = $entry; 
    }
}

$tagUrl  = $svnServer.$tagBase.'/'.((string) $maxEntry->name);
echo 'Last release was: '.$maxEntry->name.' ('.$tagUrl.')'.PHP_EOL;

$tagUrl  = $svnServer.$tagBase.'/4.0.15';

// Get current branch info
$rootSvnInfo = simplexml_load_string(shell_exec('svn info --xml '.$rootdir));
$rootSvnUrl  = $rootSvnInfo->entry->url;

// Get diff since last release
echo "Compare tag with current branch: ".$rootSvnUrl.PHP_EOL;
$plugins   = array();
$themes    = array();
$toCheck   = array();
$soap      = false;
$cli       = false;
$userGuide = false;
$diff = simplexml_load_string(shell_exec('svn diff --xml --summarize '.$tagUrl.' '.$rootSvnUrl));
foreach ($diff->xpath('paths/path') as $path) {
    $fullURL = (string) $path;

    $p = substr($fullURL, -(strlen($fullURL)-strlen($tagUrl)));

    if (preg_match('%^/documentation/cli/%', $p)) {
        $cli = true;
    }
    if (preg_match('%^/documentation/user_guide/%', $p)) {
        $userGuide = true;
    }
    $match = array();
    if (preg_match('%^(/plugins/[^/]+)/%', $p, $match)) {
        $plugins[$match[1]] = true;
    }
    if (preg_match('%^/cli/%', $p)) {
        $cli = true;
    }
    if (preg_match('%^/src/www/soap/%', $p)) {
        $soap = true;
    }
    $match = array();
    if (preg_match('%^(/src/www/themes/[^/]+)/%', $p, $match)) {
        if ($match[1] != '/src/www/themes/common') {
            $themes[$match[1]] = true;
        }
    }
}

$cmp = new ReleaseVersionComparator($tagUrl, $rootdir);

/*if (isset($p)) {
    echo "Core: ".PHP_EOL;
    $cmp->iterateOverPaths(array('/'));
}
*/


if (count($plugins) > 0) {
    echo "Plugins: ".PHP_EOL;
    $pluginCmp = new PluginReleaseVersionComparator($tagUrl, $rootdir, new FakePluginDescriptor($rootdir));
    $pluginCmp->iterateOverPaths(array_keys($plugins));
}

if (count($themes) > 0) {
    echo "Themes: ".PHP_EOL;
    $cmp->iterateOverPaths(array_keys($themes));
}

if ($soap) {
    echo "Soap path changed, please check (not automated yet)".PHP_EOL;
}

if ($cli) {
    echo "CLI sources or documentation path changed, please check (not automated yet)".PHP_EOL;
}

if ($userGuide) {
    echo "User Guide path changed, please check (not automated yet)".PHP_EOL;
}

?>