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
 *
 */

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction\Update;

use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Workflow\PostAction\Update\CIBuildValue;
use Tuleap\Tracker\Workflow\Update\PostAction;
use Workflow;

class CIBuildJsonParser implements PostActionUpdateJsonParser
{
    public function accept(array $json): bool
    {
        return isset($json['type']) && $json['type'] === 'run_job';
    }

    public function parse(Workflow $workflow, array $json): PostAction
    {
        if (! isset($json['job_url'])) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-tracker', "Mandatory attribute job_url not found in CI build action.")
            );
        }
        if (! is_string($json['job_url'])) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-tracker', "Bad job_url attribute format: string expected.")
            );
        }

        return new CIBuildValue($json['job_url']);
    }
}
