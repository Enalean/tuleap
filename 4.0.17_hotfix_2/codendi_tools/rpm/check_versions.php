<?php

// Find the first branch revision
$log = simplexml_load_string(shell_exec('svn log --xml --stop-on-copy'));
$lastEntry = count($log->logentry) - 1;
$firstRevision = (int) $log->logentry[$lastEntry]['revision'];

$toCheck = array();

// Find all added .php files
$diff = simplexml_load_string(shell_exec('svn diff --xml --summarize -r '.$firstRevision.':HEAD'));
foreach ($diff->xpath('paths/path') as $path) {
    $p = (string) $path;
    if (strpos($p, 'documentation/cli')  !== false) {
        $toCheck['documentation/cli'] = true;
    }
    $match = array();
    if (preg_match('%^plugins/([^/]+)/%', $p, $match)) {
        $toCheck['plugins/'.$match[1]] = true;
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

?>