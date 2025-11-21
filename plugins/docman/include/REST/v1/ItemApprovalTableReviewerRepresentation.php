<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
use Luracast\Restler\RestException;
use Tuleap\Docman\ApprovalTable\ApprovalTableStateMapper;
use Tuleap\REST\JsonCast;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\User\RetrieveUserById;

/**
 * @psalm-immutable
 */
final readonly class ItemApprovalTableReviewerRepresentation
{
    private function __construct(
        public MinimalUserRepresentation $user,
        public int $rank,
        public ?string $review_date,
        public string $state,
        public string $comment,
        public string $version,
    ) {
    }

    public static function build(
        Docman_ApprovalReviewer $reviewer,
        ApprovalTableStateMapper $status_mapper,
        RetrieveUserById $user_manager,
        ProvideUserAvatarUrl $provide_user_avatar_url,
    ): self {
        $user = $user_manager->getUserById((int) $reviewer->getId());
        if ($user === null) {
            throw new RestException(404);
        }

        return new self(
            MinimalUserRepresentation::build($user, $provide_user_avatar_url),
            JsonCast::toInt($reviewer->getRank()),
            JsonCast::toDate($reviewer->getReviewDate()),
            $status_mapper->getStatusStringFromStatusId((int) $reviewer->getState()),
            $reviewer->getComment() ?? '',
            (string) $reviewer->getVersion(),
        );
    }
}
