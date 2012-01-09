<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
 *
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

if ($argc < 4) {
    die("Usage: ".$argv[0]." requester shortname longname [member 1] [member 2] [...]\n");
}

// Establish connexion to the server
$client = new SoapClient('http://shunt.cro.enalean.com/soap/project/?wsdl', 
                         array('cache_wsdl' => WSDL_CACHE_NONE));

//$client->addProject(requester, shortname, longname);
$prjId = $client->addProject($argv[1], $argv[2], $argv[3]);

echo "New Project ID: $prjId\n";

for($i = 4; $i < $argc; $i++) {
    var_dump($client->addProjectMember($prjId, $argv[$i]));
}

?>