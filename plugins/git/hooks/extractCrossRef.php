<?php
/**
 * Copyright (c) Enalean 2011. All rights reserved
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
 *
 *
 * Wrap API access to cross reference extraction. The text to check
 * is expected on stdin.
 *
 * Usage: php extractCrossRef.php --group_name="gpig" --login="disciplus_simplex" --type="git_commit" --rev_id="gpig/64bf3ca"
 */

// Check script parameters
if ($argc != 5) {
    error("Wrong number of arguments");
}

$params = array();
foreach ($argv as $arg) {
    if (preg_match('/^--(.*)=(.*)$/', $arg, $matches)) {
        $params[$matches[1]] = $matches[2];
    }
}

foreach (array('group_name', 'login', 'type', 'rev_id') as $p) {
    if (!isset($params[$p]) || $params[$p] == "") {
        error("Missing parameter '$p'. (Argv: ".implode(' ', $argv).")");
    }
}

// Get stdin
$text = file_get_contents('php://stdin');

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'http://localhost:2080/api/reference/extractCross');
curl_setopt($ch, CURLOPT_USERAGENT, 'Codendi Perl Agent');
curl_setopt($ch, CURLOPT_POSTFIELDS, 'group_name='.$params['group_name'].'&login='.$params['login'].'&type='.$params['type'].'&rev_id='.$params['rev_id'].'&text='.urlencode($text));

// Output

echo $text.PHP_EOL;
curl_exec($ch);

curl_close($ch);


// Functions
function error($msg) {
    echo "*** Error: $msg".PHP_EOL;
    exit(1);
}

?>