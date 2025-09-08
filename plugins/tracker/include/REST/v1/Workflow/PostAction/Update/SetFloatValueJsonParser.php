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
 *
 */

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction\Update;

use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Workflow\PostAction\Update\SetFloatValue;
use Tuleap\Tracker\Workflow\Update\PostAction;
use Workflow;

class SetFloatValueJsonParser implements PostActionUpdateJsonParser
{
    #[\Override]
    public function accept(array $json): bool
    {
        return isset($json['type'])
            && $json['type'] === 'set_field_value'
            && isset($json['field_type'])
            && $json['field_type'] === 'float';
    }

    #[\Override]
    public function parse(Workflow $workflow, array $json): PostAction
    {
        if (! isset($json['field_id'])) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-tracker', 'Mandatory attribute field_id not found in action with type "set_field_value".')
            );
        }
        if (! is_int($json['field_id'])) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-tracker', 'Bad field_id attribute format: int expected.')
            );
        }
        if (! isset($json['value'])) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-tracker', 'Mandatory attribute value not found in with type "set_field_value".')
            );
        }
        if (! is_int($json['value']) && ! is_float($json['value'])) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-tracker', 'Bad value attribute format: float expected.')
            );
        }

        return new SetFloatValue(
            $json['field_id'],
            $json['value']
        );
    }
}
