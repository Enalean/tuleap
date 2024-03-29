#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 * Ouput all the tuleap user groups, one ssh/gitolite user is member of.
 * Example:
 * $> gitolite_membership_pgm.php vaceletm
 * site_active gpig_project_members gpig_project_admin ug_1234
 *
 * Inspired from:
 * https://github.com/sitaramc/gitolite/blob/pu/doc/big-config.mkd#_storing_usergroup_information_outside_gitolite_like_in_LDAP_
 * https://github.com/sitaramc/gitolite/blob/pu/contrib/ldap/ldap-query-example.pl
 */

use Tuleap\Project\UGroupLiteralizer;

require_once __DIR__ . '/../../../src/www/include/pre.php';

if (! isset($argv[1])) {
    echo 'Usage: ' . $argv[0] . ' username' . PHP_EOL;
    exit(1);
}

$ugroup_literalizer = new UGroupLiteralizer();
$groups             = $ugroup_literalizer->getUserGroupsForUserName($argv[1]);
if (count($groups) > 0) {
    echo implode(' ', $groups) . PHP_EOL;
}

?>
