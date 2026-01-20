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

use Codendi_HTMLPurifier;
use Docman_ApprovalReviewer;
use Docman_ApprovalTable;
use Docman_ApprovalTableVersionned;
use Docman_Item;
use Docman_NotificationsManager;
use Docman_VersionFactory;
use Tuleap\Docman\ApprovalTable\ApprovalTableStateMapper;
use Tuleap\Docman\REST\v1\ApprovalTable\ApprovalTableNotificationMapper;
use Tuleap\REST\I18NRestException;
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
        public ?int $version_id,
        public ?int $version_number,
        public string $version_label,
        public string $notification_type,
        public string $state,
        public bool $is_closed,
        public string $description,
        public string $post_processed_description,
        public array $reviewers,
        public int $reminder_occurence,
        public string $version_open_href,
    ) {
    }

    public static function build(
        Docman_Item $item,
        Docman_ApprovalTable $approval_table,
        MinimalUserRepresentation $table_owner,
        ApprovalTableStateMapper $status_mapper,
        RetrieveUserById $user_manager,
        ProvideUserAvatarUrl $provide_user_avatar_url,
        Docman_VersionFactory $version_factory,
        Docman_NotificationsManager $notifications_manager,
        Codendi_HTMLPurifier $purifier,
    ): self {
        $version_label         = '';
        $version_id            = null;
        $version_number        = null;
        $version_download_href = '';
        if ($approval_table instanceof Docman_ApprovalTableVersionned) {
            $version        = $version_factory->getSpecificVersion($item, $approval_table->getVersionNumber());
            $version_label  = (string) $version?->getLabel();
            $version_id     = (int) $version?->getId();
            $version_number = (int) $approval_table->getVersionNumber();
        }

        $description                = $approval_table->getDescription() ?? '';
        $post_processed_description = $purifier->purifyTextWithReferences($description, $item->getGroupId());
        return new self(
            JsonCast::toInt($approval_table->getId()),
            $table_owner,
            $status_mapper->getStatusStringFromStatusId((int) $approval_table->getApprovalState()),
            JsonCast::toDate($approval_table->getDate()),
            JsonCast::toBoolean($approval_table->getApprovalState() === PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED),
            $version_id,
            $version_number,
            $version_label,
            ApprovalTableNotificationMapper::fromConstantToString((int) $approval_table->getNotification()),
            match ((int) $approval_table->getStatus()) {
                PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED => 'disabled',
                PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED  => 'enabled',
                PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED   => 'closed',
                PLUGIN_DOCMAN_APPROVAL_TABLE_DELETED  => 'deleted',
                default                               => throw new I18NRestException(400, dgettext('tuleap-docman', 'Invalid approval table status')),
            },
            $approval_table->isClosed(),
            $description,
            $post_processed_description,
            array_values(array_map(
                static fn(Docman_ApprovalReviewer $reviewer) => ItemApprovalTableReviewerRepresentation::build(
                    $item,
                    $reviewer,
                    $status_mapper,
                    $user_manager,
                    $provide_user_avatar_url,
                    $version_factory,
                    $notifications_manager,
                    $purifier,
                ),
                $approval_table->getReviewerArray(),
            )),
            (int) $approval_table->getNotificationOccurence(),
            $version_download_href
        );
    }
}
