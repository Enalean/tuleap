<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Routing;

use PFUser;
use Planning_MilestoneFactory;
use Tuleap\Request\NotFoundException;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsAllowedChecker;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsNotAllowedException;

class MilestoneExtractor
{
    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var MilestoneIsAllowedChecker
     */
    private $checker;

    public function __construct(Planning_MilestoneFactory $milestone_factory, MilestoneIsAllowedChecker $checker)
    {
        $this->milestone_factory = $milestone_factory;
        $this->checker           = $checker;
    }

    /**
     * @throws NotFoundException
     */
    public function getMilestone(PFUser $user, array $variables): \Planning_Milestone
    {
        $milestone = $this->milestone_factory->getBareMilestoneByArtifactId($user, (int) $variables['id']);
        if (! $milestone) {
            throw new NotFoundException(dgettext('tuleap-taskboard', "Milestone not found."));
        }

        if ((string) $milestone->getProject()->getUnixNameMixedCase() !== (string) $variables['project_name']) {
            throw new NotFoundException(dgettext('tuleap-taskboard', "Milestone not found."));
        }

        try {
            $this->checker->checkMilestoneIsAllowed($milestone);

            return $milestone;
        } catch (MilestoneIsNotAllowedException $exception) {
            throw new NotFoundException($exception->getMessage());
        }
    }
}
