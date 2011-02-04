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

// Get current branch info
$rootSvnInfo = simplexml_load_string(shell_exec('svn info --xml '.$rootdir));
$rootSvnUrl  = $rootSvnInfo->entry->url;

// Get diff since last release
echo "Compare tag with current branch: ".$rootSvnUrl.PHP_EOL;
$plugins = array();
$themes  = array();
$toCheck = array();
$diff = simplexml_load_string(shell_exec('svn diff --xml --summarize '.$tagUrl.' '.$rootSvnUrl));
foreach ($diff->xpath('paths/path') as $path) {
    $fullURL = (string) $path;

    $p = substr($fullURL, -(strlen($fullURL)-strlen($tagUrl)));

    if (preg_match('%^/documentation/cli/%', $p)) {
        $toCheck['documentation/cli'] = true;
    }
    $match = array();
    if (preg_match('%^(/plugins/[^/]+)/%', $p, $match)) {
        //$toCheck['plugins/'.$match[1]] = true;
        $plugins[$match[1]] = true;
    }
    if (preg_match('%^/cli/%', $p)) {
        $toCheck['cli'] = true;
    }
    if (preg_match('%^/src/www/soap/%', $p)) {
        $toCheck['src/www/soap'] = true;
    }
    $match = array();
    if (preg_match('%^(/src/www/themes/[^/]+)/%', $p, $match)) {
        if ($match[1] != 'common') {
            $themes[$match[1]] = true;
        }
    }
}

echo "Please check: ".PHP_EOL;
foreach ($toCheck as $path => $nop) {
    echo "\t".$path.PHP_EOL;
}

echo "Plugins: ".PHP_EOL;
$pluginCmp = new PluginReleaseVersionComparator($tagUrl, $rootdir, new FakePluginDescriptor($rootdir));
$pluginCmp->iterateOverPaths(array_keys($plugins));
/*foreach ($plugins as $plugin => $nop) {
    $relVersionPath = '/plugins/'.$plugin.'/VERSION';

    // Find current version number
    $curVersion  = $pluginCmp->getCurrentVersion($relVersionPath);
    $prevVersion = $pluginCmp->getPreviousVersion($relVersionPath);

    if (version_compare($curVersion, $prevVersion, '<=')) {
        echo "\t".$plugin.": ".$curVersion.' (Previous release was: '.$prevVersion.')'.PHP_EOL;        
    }
    }*/




?>