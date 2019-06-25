<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction\Update;

use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Workflow\PostAction\Update\FrozenFieldsValue;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\IncompatibleWorkflowModeException;
use Tuleap\Tracker\Workflow\Update\PostAction;
use Workflow;

class FrozenFieldsJsonParser implements PostActionUpdateJsonParser
{
    public const POSTACTION_TYPE = "frozen_fields";

    public function accept(array $json): bool
    {
        return isset($json['type']) && $json['type'] === self::POSTACTION_TYPE;
    }

    /**
     * @throws IncompatibleWorkflowModeException
     * @throws I18NRestException
     */
    public function parse(Workflow $workflow, array $json): PostAction
    {
        if ($workflow->isAdvanced()) {
            throw new IncompatibleWorkflowModeException(self::POSTACTION_TYPE);
        }

        if (! isset($json['field_ids'])) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-tracker', 'Mandatory attribute field_ids not found in action with type "frozen_fields".')
            );
        }

        if (! is_array($json['field_ids'])) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-tracker', "Bad field_ids attribute format: array of integer expected.")
            );
        }

        if (count($json['field_ids']) === 0) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-tracker', "field_ids attribute must not be empty.")
            );
        }

        foreach ($json['field_ids'] as $field_id) {
            if (! is_int($field_id)) {
                throw new I18NRestException(
                    400,
                    dgettext('tuleap-tracker', "Bad field_ids attribute format: array of integer expected.")
                );
            }
        }

        return new FrozenFieldsValue($json['field_ids']);
    }
}
