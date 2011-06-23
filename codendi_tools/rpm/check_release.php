<?php

require_once 'ReleaseVersionComparator.php';

$verbose = false;
if (isset($argv[1])) {
    $verbose = true;
}

// Gather RPMs info
$rpms = array();
$spec = file(dirname(__FILE__).'/codendi.spec', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($spec as $line) {
    $m1 = array();
    if (preg_match('/^%package (.*)-(.*)$/', trim($line), $m1)) {
        $rpms[$m1[1]][] = $m1[2];
    }
}

$rootdir = realpath(dirname(__FILE__).'/../..');
chdir($rootdir);

$svnServer = 'https://tuleap.net/svnroot/tuleap';
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
//$tagUrl  = $svnServer.$tagBase.'/4.0.15';
echo 'Last release was: '.$maxEntry->name.' ('.$tagUrl.')'.PHP_EOL;

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
    if ($verbose) {
        echo "\t$p".PHP_EOL;
    }

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
    $cmp->iterateOverPaths(array('/'), $verbose);
}
*/


if (count($plugins) > 0) {
    echo "Plugins: ".PHP_EOL;
    $pluginCmp = new PluginReleaseVersionComparator($tagUrl, $rootdir, new FakePluginDescriptor($rootdir));
    $pluginCmp->iterateOverPaths(array_keys($plugins), $rpms['plugin'], $verbose);
}

if (count($themes) > 0) {
    echo "Themes: ".PHP_EOL;
    $cmp->iterateOverPaths(array_keys($themes), $rpms['theme'], $verbose);
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