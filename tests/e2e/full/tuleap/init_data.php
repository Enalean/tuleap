#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

declare(strict_types=1);

require_once __DIR__ . '/../../../../src/www/include/pre.php';

$db = \Tuleap\DB\DBFactory::getMainTuleapDBConnection()->getDB();
$db->run('DELETE FROM password_configuration');
$db->run("UPDATE service SET is_active=true WHERE short_name='wiki'");
// Add nature for frs plugin tests
$db->run("INSERT INTO plugin_tracker_artifactlink_natures (shortname, forward_label, reverse_label) VALUES ('fixed_in', 'Fixed in', 'Fixed by')");
