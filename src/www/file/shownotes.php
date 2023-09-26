<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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

use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\ReleasePermissionManager;

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/file_utils.php';

$release_id = (int) $request->getValidated('release_id', 'uint', 0);
if ($release_id === 0) {
    exit_error($GLOBALS['Language']->getText('file_shownotes', 'not_found_err'), $GLOBALS['Language']->getText('file_shownotes', 'release_not_found'));
}

$frsrf   = new FRSReleaseFactory();
$user    = UserManager::instance()->getCurrentUser();
$release = $frsrf->getFRSReleaseFromDb($release_id);

$permission_manager         = FRSPermissionManager::build();
$release_permission_manager = new ReleasePermissionManager($permission_manager, $frsrf);
if (
    $release === null ||
    $release_permission_manager->canUserSeeRelease($user, $release, $release->getProject()) === false
) {
    exit_error($Language->getText('file_shownotes', 'not_found_err'), $Language->getText('file_shownotes', 'release_not_found'));
}

$group_id = $release->getGroupID();
file_utils_header(['title' => $Language->getText('file_shownotes', 'release_notes')]);

$hp = Codendi_HTMLPurifier::instance();

$HTML->box1_top($Language->getText('file_shownotes', 'notes'));

echo '<h3>' . $Language->getText('file_shownotes', 'release_name') . ': <A HREF="showfiles.php?group_id=' . $group_id . '">' . $hp->purify($release->getName()) . '</A></H3>
    <P>';

/*
    Show preformatted or plain notes/changes
*/
$purify_level = CODENDI_PURIFIER_BASIC;
if ($release->isPreformatted()) {
    echo '<PRE>' . PHP_EOL;
    $purify_level = CODENDI_PURIFIER_BASIC_NOBR;
}
echo '<B>' . $Language->getText('file_shownotes', 'notes') . ':</B>' . PHP_EOL
     . $hp->purify($release->getNotes(), $purify_level, $group_id) .
    '<HR NOSHADE SIZE=1>' .
    '<B>' . $Language->getText('file_shownotes', 'changes') . ':</B>' . PHP_EOL
    . $hp->purify($release->getChanges(), $purify_level, $group_id);
if ($release->isPreformatted()) {
    echo '</PRE>';
}

$crossref_fact = new CrossReferenceFactory($release_id, ReferenceManager::REFERENCE_NATURE_RELEASE, $group_id);
$crossref_fact->fetchDatas();
if ($crossref_fact->getNbReferences() > 0) {
    echo '<hr noshade>';
    echo '<b> ' . $Language->getText('cross_ref_fact_include', 'references') . '</b>';
    $crossref_fact->DisplayCrossRefs();
}


$HTML->box1_bottom();

file_utils_footer([]);
