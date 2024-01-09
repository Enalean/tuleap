<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

declare(strict_types=1);

use Tuleap\Taskboard\AgileDashboard\TaskboardUsage;
use Tuleap\Taskboard\AgileDashboard\TaskboardUsageDao;

require_once __DIR__ . '/../../../../src/www/include/pre.php';
require_once __DIR__ . '/../../../../tests/rest/vendor/autoload.php';
require_once __DIR__ . '/../../include/taskboardPlugin.php';

$project = ProjectManager::instance()->getProjectByUnixName(TestDataBuilder::PROJECT_PRIVATE_MEMBER_SHORTNAME);
if (! $project || $project->isError()) {
    throw new RuntimeException('Need to get ' . TestDataBuilder::PROJECT_PRIVATE_MEMBER_SHORTNAME);
}

// required for MilestonesTest::testGETResourcesCardwall
// otherwise it's Taskboard by default (for all new projects)
$taskboard_dao = new TaskboardUsageDao();
$taskboard_dao->updateBoardTypeByProjectId((int) $project->getID(), TaskboardUsage::OPTION_CARDWALL);
