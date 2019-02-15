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

use Tuleap\Tracker\Workflow\PostAction\Update\CIBuild;

class CIBuildValidator
{
    /**
     * @var PostActionIdValidator
     */
    private $ids_validator;

    public function __construct(PostActionIdValidator $ids_validator)
    {
        $this->ids_validator = $ids_validator;
    }

    /**
     * @throws InvalidPostActionException
     */
    public function validate(CIBuild ...$ci_builds): void
    {
        try {
            $this->ids_validator->validate(...$ci_builds);
        } catch (DuplicatePostActionException $e) {
            throw new InvalidPostActionException(
                dgettext('tuleap-tracker', "There should not be duplicate 'run_job' ids.")
            );
        }
        foreach ($ci_builds as $ci_build) {
            $this->validateCIBuild($ci_build);
        }
    }

    /**
     * @throws InvalidPostActionException
     */
    private function validateCIBuild(CIBuild $ci_build)
    {
        $job_url = $ci_build->getJobUrl();
        if (! $this->isUrlValid($job_url)) {
            throw new InvalidPostActionException(
                dgettext('tuleap-tracker', "The 'job_url' attribute must be a valid URL.")
            );
        }
    }

    private function isUrlValid($job_url)
    {
        return preg_match('#' . \Transition_PostAction_CIBuild::JOB_URL_PATTERN . '#', $job_url) > 0;
    }
}
