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

namespace Tuleap\Docman\REST\v1\ApprovalTable;

use Docman_ApprovalReviewer;
use Docman_ApprovalTable;
use Docman_ApprovalTableReviewerFactory;
use Docman_ApprovalTableVersionned;
use Docman_Item;
use Docman_NotificationsManager;
use EventManager;
use Luracast\Restler\RestException;
use PFUser;
use Psr\Clock\ClockInterface;
use Tuleap\Docman\ApprovalTable\ApprovalTableStateMapper;
use Tuleap\REST\I18NRestException;

final readonly class ApprovalTableReviewUpdater
{
    public function __construct(
        private Docman_ApprovalTableReviewerFactory $reviewer_factory,
        private Docman_NotificationsManager $notifications_manager,
        private EventManager $event_manager,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws RestException
     */
    public function update(Docman_Item $item, PFUser $user, Docman_ApprovalTable $table, ApprovalTableReviewPutRepresentation $representation): void
    {
        if (! $table->isEnabled() || ! $this->reviewer_factory->isReviewer((int) $user->getId())) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'You cannot review this approval table'));
        }

        $review = new Docman_ApprovalReviewer();
        $review->setId((int) $user->getId());
        $review_state = new ApprovalTableStateMapper()->getStatusIdFromStatusString($representation->review);
        $review->setState($review_state);
        $review->setComment($representation->comment);
        $version = $table instanceof Docman_ApprovalTableVersionned ? (int) $table->getVersionNumber() : null;
        if ($review_state !== PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET) {
            $review->setVersion($version);
            $review->setReviewDate($this->clock->now()->getTimestamp());
        } else {
            $review->setVersion(null);
            $review->setReviewDate(null);
        }

        if (! $this->reviewer_factory->updateReview($review)) {
            throw new I18NRestException(500, dgettext('tuleap-docman', 'An error occured while saving review'));
        }
        $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_APPROVAL_TABLE_COMMENT, [
            'item'       => $item,
            'version_nb' => $version,
            'table'      => $table,
            'review'     => $review,
        ]);

        $already_monitored = $this->notifications_manager->userExists($user->getId(), $item->getId());
        if ($representation->notification && ! $already_monitored) {
            $this->notifications_manager->add($user->getId(), $item->getId());
        }
        if (! $representation->notification && $already_monitored) {
            $this->notifications_manager->removeUser($user->getId(), $item->getId());
        }
    }
}
