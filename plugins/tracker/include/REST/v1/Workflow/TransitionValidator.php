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

namespace Tuleap\Tracker\REST\v1\Workflow;

use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Workflow\Transition\TransitionCreationParameters;
use Workflow;

class TransitionValidator
{
    /**
     * Checks params from_id and to_id.
     * Destination id must exist for the workflow field.
     * Source id must exist for the workflow field.
     * If source is a new artefact (from_id = 0), it returns null value.
     *
     * @throws I18NRestException 400
     * @throws I18NRestException 404
     */
    public function validateForCreation(Workflow $workflow, int $param_from_id, int $param_to_id): TransitionCreationParameters
    {
        if ($param_from_id === $param_to_id) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-tracker', 'The same value cannot be source and destination at the same time.')
            );
        }

        $from_id = $param_from_id === 0 ? null : $param_from_id;
        $to_id   = $param_to_id;

        if ($workflow->getTransition($from_id, $to_id) !== null) {
            throw new I18NRestException(400, dgettext('tuleap-tracker', 'This transition already exists.'));
        }

        $all_field_values = $workflow->getAllFieldValues();

        if ($from_id > 0 && array_key_exists($from_id, $all_field_values) === false) {
            throw new I18NRestException(404, dgettext('tuleap-tracker', 'Source id does not exist.'));
        }
        if (array_key_exists($to_id, $all_field_values) === false) {
            throw new I18NRestException(404, dgettext('tuleap-tracker', 'Destination id does not exist.'));
        }

        return new TransitionCreationParameters($from_id, $to_id);
    }
}
