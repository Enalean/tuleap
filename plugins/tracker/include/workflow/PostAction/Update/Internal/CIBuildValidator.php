<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Tuleap\Tracker\Workflow\Update\PostAction;

class CIBuildValidator
{
    /**
     * @throws DuplicateCIBuildPostAction
     * @throws InvalidCIBuildPostActionException
     */
    public function validate(PostActionCollection $new_post_actions): void
    {
        $ci_builds = $new_post_actions->getCIBuildActions();
        $ids       = $this->extractPostActionIds(...$ci_builds);

        if ($this->hasDuplicateIds(...$ids)) {
            throw new DuplicateCIBuildPostAction();
        }

        foreach ($ci_builds as $ci_build) {
            $this->validateCIBuild($ci_build);
        }
    }

    /**
     * @return int[]
     */
    private function extractPostActionIds(PostAction ...$actions): array
    {
        $ids = array_map(
            function (PostAction $action) {
                return $action->getId();
            },
            $actions
        );
        return array_filter(
            $ids,
            function ($id) {
                return $id !== null;
            }
        );
    }

    private function hasDuplicateIds(int ...$ids): bool
    {
        return count($ids) !== count(array_unique($ids));
    }

    /**
     * @throws InvalidCIBuildPostActionException
     */
    private function validateCIBuild(CIBuild $ci_build)
    {
        $job_url = $ci_build->getJobUrl();
        if (! $this->isUrlValid($job_url)) {
            throw new InvalidCIBuildPostActionException(
                dgettext('tuleap-tracker', 'The job_url attribute must be a valid URL.')
            );
        }
    }

    private function isUrlValid($job_url)
    {
        return preg_match('#' . \Transition_PostAction_CIBuild::JOB_URL_PATTERN . '#', $job_url) > 0;
    }
}
