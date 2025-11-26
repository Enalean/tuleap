<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1;

use Docman_ApprovalReviewer;
use Docman_ApprovalTable;
use Docman_ApprovalTableFactoriesFactory;
use Docman_ApprovalTableVersionned;
use Docman_Item;
use Docman_VersionFactory;
use Tuleap\Docman\ApprovalTable\ApprovalTableStateMapper;
use Tuleap\REST\JsonCast;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\User\RetrieveUserById;

/**
 * @psalm-immutable
 */
final readonly class ItemApprovalTableRepresentation
{
    private function __construct(
        public ?int $id,
        public MinimalUserRepresentation $table_owner,
        public string $approval_state,
        public ?string $approval_request_date,
        public ?bool $has_been_approved,
        public ?int $version_number,
        public string $version_label,
        public string $notification_type,
        public bool $is_closed,
        public string $description,
        public array $reviewers,
    ) {
    }

    public static function build(
        Docman_Item $item,
        Docman_ApprovalTable $approval_table,
        MinimalUserRepresentation $table_owner,
        ApprovalTableStateMapper $status_mapper,
        Docman_ApprovalTableFactoriesFactory $factory,
        RetrieveUserById $user_manager,
        ProvideUserAvatarUrl $provide_user_avatar_url,
        Docman_VersionFactory $version_factory,
    ): self {
        if ($approval_table instanceof Docman_ApprovalTableVersionned) {
            $version_label = (string) $version_factory->getSpecificVersion($item, $approval_table->getVersionNumber())?->getLabel();
        } else {
            $version_label = '';
        }

        return new self(
            JsonCast::toInt($approval_table->getId()),
            $table_owner,
            $status_mapper->getStatusStringFromStatusId((int) $approval_table->getApprovalState()),
            JsonCast::toDate($approval_table->getDate()),
            JsonCast::toBoolean($approval_table->getApprovalState() === PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED),
            $approval_table instanceof Docman_ApprovalTableVersionned ? (int) $approval_table->getVersionNumber() : null,
            $version_label,
            $factory->getFromItem($item)?->getNotificationTypeName($approval_table->getNotification()) ?? '',
            $approval_table->isClosed(),
            $approval_table->getDescription() ?? '',
            array_map(
                static fn(Docman_ApprovalReviewer $reviewer) => ItemApprovalTableReviewerRepresentation::build(
                    $item,
                    $reviewer,
                    $status_mapper,
                    $user_manager,
                    $provide_user_avatar_url,
                    $version_factory,
                ),
                $approval_table->getReviewerArray(),
            ),
        );
    }
}
