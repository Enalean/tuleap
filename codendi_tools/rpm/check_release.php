<?php

require_once 'FakePluginDescriptor.php';

$rootdir = realpath(dirname(__FILE__).'/../..');
chdir($rootdir);

// Get last tag
$tagBase = '/contrib/st/intg/Codendi-ST-4.0/tags';
$tags = simplexml_load_string(shell_exec('svn ls --xml ^'.$tagBase));
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

$lastReleaseRevision = (int) $maxEntry->commit['revision'];
$tagUrl  = $tagBase.'/'.((string) $maxEntry->name);
echo 'Last release was: '.$maxEntry->name.' ('.$tagUrl.' r'.$lastReleaseRevision.')'.PHP_EOL;

// Get diff since last release
$plugins = array();
$diff = simplexml_load_string(shell_exec('svn diff --xml --summarize -r '.$lastReleaseRevision.':HEAD'));
foreach ($diff->xpath('paths/path') as $path) {
    $p = (string) $path;

    if (strpos($p, 'documentation/cli')  !== false) {
        $toCheck['documentation/cli'] = true;
    }
    $match = array();
    if (preg_match('%^plugins/([^/]+)/%', $p, $match)) {
        $toCheck['plugins/'.$match[1]] = true;
        $plugins[$match[1]] = true;
    }
    if (preg_match('%^cli/%', $p)) {
        $toCheck['cli'] = true;
    }
    if (preg_match('%^src/www/soap/%', $p)) {
        $toCheck['src/www/soap'] = true;
    }
    $match = array();
    if (preg_match('%^(src/www/themes/[^/]+)/%', $p, $match)) {
        if ($match[1] != 'common') {
            $toCheck[$match[1]] = true;
        }
    }
}

echo "Please check: ".PHP_EOL;
foreach ($toCheck as $path => $nop) {
    echo "\t".$path.PHP_EOL;
}


echo "Plugins: ".PHP_EOL;
$fpd = new FakePluginDescriptor($rootdir);
foreach ($plugins as $plugin => $nop) {
    $curVersion = -1;
    $preVersion = -1;
    $pluginRoot = '/plugins/'.$plugin;
    $descLoaded = false;

    $relVersionPath = $pluginRoot.'/VERSION';

    // Find current version number
    if (file_exists($rootdir.$relVersionPath)) {
        $curVersion = trim(file_get_contents($rootdir.$relVersionPath));
    } else {
        $desc       = $fpd->getDescriptor($plugin);
        $curVersion = $desc->getVersion();
        $descLoaded = true;
    }

    // First try to get the VERSION if any
    $oldVersionPath = 'VERSION.'.$plugin.'.'.$lastReleaseRevision;
    $output = array();
    $retVal = false;
    exec('svn cat -r'.$lastReleaseRevision.' ^'.$tagUrl.$relVersionPath.' 2>/dev/null > '.$oldVersionPath, $output, $retVal);
    if ($retVal === 0) {
        $prevVersion = trim(file_get_contents($oldVersionPath));
    } else {
        if (!$descLoaded) {
            $path    = $fpd->findDescriptor($rootdir.$pluginRoot);
            $relPath = substr($path, -(strlen($path)-strlen($rootdir)));

            $oldDescPath = basename($relPath).'.'.$lastReleaseRevision;
            shell_exec('svn cat -r'.$lastReleaseRevision.' ^'.$tagUrl.$relPath.' > '.$oldDescPath);
        
            // Get descriptor
            $oldDesc     = $fpd->getDescriptorFromFile($plugin, $oldDescPath);
            $prevVersion = $oldDesc->getVersion();

            unlink($oldDescPath);
        }
    }
    unlink($oldVersionPath);

    if (version_compare($curVersion, $prevVersion, '<=')) {
        echo "\t".$plugin.": ".$curVersion.' (Previous release was: '.$prevVersion.')'.PHP_EOL;        
    }
}

?>