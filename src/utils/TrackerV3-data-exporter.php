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

require_once 'pre.php';

require_once 'common/tracker/ArtifactXMLExporter.class.php';

if ($argc != 3) {
    echo 'Usage: '.basename($argv[0]).' tracker_id /path/to/archive.zip'.PHP_EOL;
    exit(1);
}

$atid         = $argv[1];
$archive_path = $argv[2];

if (file_exists($archive_path)) {
    echo "*** ERROR: File $archive_path already exists.".PHP_EOL;
    exit(1);
}

$xml      = new DOMDocument("1.0", "UTF8");
$logger   = new Log_ConsoleLogger();
$archive  = new ZipArchive();
if ($archive->open($archive_path, ZipArchive::CREATE) !== true) {
    echo '*** ERROR: Cannot create archive: '.$archive_path;
    exit(1);
}
$exporter = new ArtifactXMLExporter(new ArtifactXMLExporterDao(), $archive, $xml, $logger);
$exporter->exportTrackerData($atid);

$xsl = new DOMDocument();
$xsl->load(dirname(__FILE__).'/xml/indent.xsl');

$proc = new XSLTProcessor();
$proc->importStyleSheet($xsl);

$archive->addFromString('artifacts.xml', $proc->transformToXML($xml));

$archive->close();

$logger->dump();
