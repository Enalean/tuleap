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

namespace Tuleap\Docman\ApprovalTable;

use Exception;
use Luracast\Restler\RestException;
use Tuleap\REST\I18NRestException;

final class ApprovalTableStateMapper
{
    /**
     * @param int $state_id the ID of the approval table state.
     * @return string The label corresponding to $state_id
     * @throws Exception
     *
     * @psalm-mutation-free
     */
    public function getStatusStringFromStatusId(int $state_id): string
    {
        $statuses_map = [
            PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET    => dgettext('tuleap-docman', 'Not yet'),
            PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED  => dgettext('tuleap-docman', 'Approved'),
            PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED  => dgettext('tuleap-docman', 'Rejected'),
            PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED => dgettext('tuleap-docman', 'Commented'),
            PLUGIN_DOCMAN_APPROVAL_STATE_DECLINED  => dgettext('tuleap-docman', 'Declined'),
        ];

        if (! isset($statuses_map[$state_id])) {
            throw new Exception(
                sprintf(
                    'Approval table state id %s does not match a valid state.',
                    $state_id
                )
            );
        }

        return $statuses_map[$state_id];
    }

    /**
     * @throws Exception
     *
     * @psalm-mutation-free
     */
    public function getStatusStringNotTranslatedFromStatusId(int $status_id): string
    {
        return match ($status_id) {
            PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET    => 'not_yet',
            PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED  => 'approved',
            PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED  => 'rejected',
            PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED => 'comment_only',
            PLUGIN_DOCMAN_APPROVAL_STATE_DECLINED  => 'will_not_review',
            default                                => throw new Exception(
                sprintf(
                    'Approval table state id %s does not match a valid state.',
                    $status_id
                )
            ),
        };
    }

    /**
     * @throws RestException
     *
     * @psalm-mutation-free
     */
    public function getStatusIdFromStatusString(string $status_string): int
    {
        return match ($status_string) {
            'not_yet'         => PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET,
            'approved'        => PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED,
            'rejected'        => PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED,
            'comment_only'    => PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED,
            'will_not_review' => PLUGIN_DOCMAN_APPROVAL_STATE_DECLINED,
            default           => throw new I18NRestException(
                400,
                sprintf(
                    dgettext('tuleap-docman', 'Approval table state string %s does not match a valid state.'),
                    $status_string,
                ),
            ),
        };
    }
}
