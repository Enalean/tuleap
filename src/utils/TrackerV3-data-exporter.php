<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once __DIR__ . '/../www/include/pre.php';

$debug           = false;
$overwrite       = false;
$atid            = null;
$archive_path    = null;
$debug_option    = getopt('d');
$overwrite_option = getopt('o');

if (isset($debug_option['d'])) {
    $debug = true;
}

if (isset($overwrite_option['o'])) {
    $overwrite = true;
}

for ($i = 1; $i < $argc; ++$i) {
    if ($argv[$i] == '-d') {
        continue;
    }
    if ($atid === null) {
        $atid = $argv[$i];
    } elseif ($archive_path === null) {
        $archive_path = $argv[$i];
    }
}

if ($atid === null || $archive_path === null) {
    echo 'Usage: ' . basename($argv[0]) . ' [-d] tracker_id /path/to/archive.zip' . PHP_EOL;
    exit(1);
}

if (! $overwrite && file_exists($archive_path)) {
    echo "*** ERROR: File $archive_path already exists." . PHP_EOL;
    exit(1);
}

try {
    $xml      = new DOMDocument("1.0", "UTF8");
    $logger   = new Log_ConsoleLogger();
    $archive  = new ZipArchive();
    if ($archive->open($archive_path, ZipArchive::CREATE) !== true) {
        echo '*** ERROR: Cannot create archive: ' . $archive_path;
        exit(1);
    }

    $dao                 = new ArtifactXMLExporterDao();
    $node_helper         = new ArtifactXMLNodeHelper($xml);
    $attachment_exporter = new ArtifactAttachmentXMLZipper($node_helper, $dao, $archive, $debug);

    $exporter = new ArtifactXMLExporter($dao, $attachment_exporter, $node_helper, $logger);
    $exporter->exportTrackerData($atid);

    if (! $debug) {
        $validator = new XML_RNGValidator();
        $validator->validate(simplexml_import_dom($xml), realpath(__DIR__ . '/../../plugins/tracker/resources/artifacts.rng'));
    }

    $xml_security = new XML_Security();
    $xml_security->enableExternalLoadOfEntities();

    $xsl = new DOMDocument();
    $xsl->load(dirname(__FILE__) . '/xml/indent.xsl');

    $proc = new XSLTProcessor();
    $proc->importStyleSheet($xsl);

    $archive->addFromString('artifacts.xml', $proc->transformToXML($xml));
    $xml_security->disableExternalLoadOfEntities();

    $archive->close();
} catch (XML_ParseException $exception) {
    foreach ($exception->getErrors() as $error) {
        echo $error . PHP_EOL;
    }
}
