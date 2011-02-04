<?php

require_once 'FakePluginDescriptor.php';

$rootdir = realpath(dirname(__FILE__).'/../..');
chdir($rootdir);

// Get last tag
$tags = simplexml_load_string(shell_exec('svn ls --xml ^/contrib/st/intg/Codendi-ST-4.0/tags'));
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
echo 'Last release was: '.$maxEntry->name.' ('.$lastReleaseRevision.')'.PHP_EOL;


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
    $desc = $fpd->getDescriptor($plugin);

    $oldDescPath = $plugin.'PluginDescriptor.class.php.'.$lastReleaseRevision;

    shell_exec('svn cat -r'.$lastReleaseRevision.' '.$rootdir.'/plugins/'.$plugin.'/include/LdapPluginDescriptor.class.php > '.$oldDescPath);

    $oldDesc = $fpd->getDescriptorFromFile($plugin, $oldDescPath);

    echo "\t".$plugin.": ".$desc->getVersion().' (Previous release was: '.$oldDesc->getVersion().')'.PHP_EOL;
    break;
}
