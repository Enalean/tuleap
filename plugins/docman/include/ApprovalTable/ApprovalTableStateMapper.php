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

namespace Tuleap\Docman\ApprovalTable;

class ApprovalTableStateMapper
{
    /**
     * @param int $state_id the ID of the approval table state.
     * @return string The label corresponding to $state_id
     * @throws \Exception
     */
    public function getStatusStringFromStatusId(int $state_id): string
    {
        $statuses_map = [
            PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET    => dgettext('tuleap-docman', 'Not yet'),
            PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED  => dgettext('tuleap-docman', 'Approved'),
            PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED  => dgettext('tuleap-docman', 'Rejected'),
            PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED => dgettext('tuleap-docman', 'Commented'),
            PLUGIN_DOCMAN_APPROVAL_STATE_DECLINED  => dgettext('tuleap-docman', 'Declined')
        ];

        if (! isset($statuses_map[$state_id])) {
            throw new \Exception(
                sprintf(
                    'Approval table state id %s does not match a valid state.',
                    $state_id
                )
            );
        }

        return $statuses_map[$state_id];
    }
}
