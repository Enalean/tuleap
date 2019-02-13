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
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuild;
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
        if (isset($json['id']) && !is_int($json['id'])) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-tracker', "Bad id attribute format: int expected.")
            );
        }
        if (!isset($json['job_url'])) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-tracker', "Mandatory attribute job_url not found in CI build action.")
            );
        }
        if (!is_string($json['job_url'])) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-tracker', "Bad job_url attribute format: string expected.")
            );
        }

        // In workflow simple mode, we drop and recreate all post actions. Therefore, the $id must be null to recreate them
        $id = null;
        if ($workflow->isAdvanced()) {
            $id = $json['id'] ?? null;
        }

        return new CIBuild(
            $id,
            $json['job_url']
        );
    }
}
