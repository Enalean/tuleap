<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

$run_dir    = $argv[1];
$output_dir = $argv[2];

$xml = simplexml_load_string(<<<XML
<?xml version='1.0'?>
<phpunit bootstrap="$run_dir/bootstrap.php">
  <testsuites>
    <testsuite name="Tuleap SOAP tests">
    </testsuite>
  </testsuites>
  <logging>
    <log type="junit" target="$output_dir/soap_tests.xml" logIncompleteSkipped="true"/>
  </logging>
</phpunit>
XML
);

$src_dir = realpath(dirname(__FILE__).'/../..');

$xml->testsuites[0]->testsuite[0]->addChild('directory', $src_dir."/tests/soap");

foreach (glob($src_dir.'/plugins/*/tests/soap') as $directory) {
    $xml->testsuites[0]->testsuite[0]->addChild('directory', $directory);
}

// Write the XML config
$xml->asXML("$run_dir/suite.xml");

// Write the bootstrap file
file_put_contents("$run_dir/bootstrap.php", '<?php'.PHP_EOL.'require_once "'.$run_dir.'/vendor/autoload.php";');
