<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use Tuleap\Reference\CrossReference;

require_once __DIR__ . '/../include/pre.php';

$target_id   = $request->get('target_id');
$target_gid  = $request->get('target_gid');
$target_type = $request->get('target_type');
$target_key  = $request->get('target_key');

$source_id   = $request->get('source_id');
$source_gid  = $request->get('source_gid');
$source_type = $request->get('source_type');
 $source_key = $request->get('source_key');

$user = UserManager::instance()->getCurrentUser();

$project_admin = $user->isMember($target_gid, 'A');
if (! $project_admin) {
    $project_admin_source = $user->isMember($source_gid, 'A');
    if ($project_admin_source) {
           $project_admin = true;
    }
}

if ($project_admin) {
    $cross_reference   = new CrossReference(
        $source_id,
        $source_gid,
        $source_type,
        $source_key,
        $target_id,
        $target_gid,
        $target_type,
        $target_key,
        (int) $user->getId()
    );
    $reference_manager = new ReferenceManager();
    $reference_manager->removeCrossReference($cross_reference);
}
