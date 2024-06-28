<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin\ArtifactsDeletion;

use Tuleap\Request\CSRFSynchronizerTokenInterface;

final readonly class ArtifactsDeletionInTrackerAdminPresenter
{
    public bool $is_deletion_impossible;
    public string $error_message;

    public function __construct(
        public CSRFSynchronizerTokenInterface $csrf_token,
        public string $url_to_deletion_confirmation,
        int $artifacts_deletion_limit,
        int $artifacts_deletion_count,
    ) {
        $is_deletion_allowed     = $artifacts_deletion_limit > 0;
        $has_remaining_deletions = $artifacts_deletion_count < $artifacts_deletion_limit;

        $this->is_deletion_impossible = ! $is_deletion_allowed || ! $has_remaining_deletions;
        $this->error_message          = $this->getErrorMessage($is_deletion_allowed, $has_remaining_deletions);
    }

    private function getErrorMessage(bool $is_deletion_allowed, bool $has_remaining_deletions): string
    {
        if (! $is_deletion_allowed) {
            return dgettext('tuleap-tracker', 'Artifacts deletion is deactivated. Please contact your site administrator.');
        }

        if (! $has_remaining_deletions) {
            return dgettext('tuleap-tracker', 'You have reached the limit of artifacts deletion for the next 24 hours. Please come back later.');
        }

        return '';
    }
}
