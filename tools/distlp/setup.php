#!/opt/remi/php73/root/usr/bin/php
<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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
 *
 */

require_once __DIR__ . '/../Configuration/vendor/autoload.php';

if (isset($options['tuleap-base-dir'])) {
    $tuleap_base_dir = $options['tuleap-base-dir'];
}

$distributed_svn = new Tuleap\Configuration\Setup\DistributedSVN();
$distributed_svn->main($options);
