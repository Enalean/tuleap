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

use Docman_ApprovalTable;
use Docman_ApprovalTableFactory;
use Docman_ApprovalTableReviewerFactory;
use Luracast\Restler\RestException;
use Throwable;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\REST\I18NRestException;
use Tuleap\User\RetrieveUserById;

final readonly class ApprovalTableUpdater
{
    public function __construct(
        private RetrieveUserById $user_retriever,
        private Docman_ApprovalTableFactory $table_factory,
        private Docman_ApprovalTableReviewerFactory $reviewer_factory,
        private DBTransactionExecutor $transaction_executor,
    ) {
    }

    /**
     * @throws RestException
     */
    public function update(Docman_ApprovalTable $table, ApprovalTablePutRepresentation $representation): void
    {
        try {
            $this->transaction_executor->execute(function () use ($table, $representation): void {
                $owner = $this->user_retriever->getUserById($representation->owner);
                if ($owner === null) {
                    throw new I18NRestException(400, sprintf(
                        dgettext('tuleap-docman', 'User %d not found'),
                        $representation->owner,
                    ));
                }

                $table_status       = ApprovalTableStatusMapper::fromStringToConstant($representation->status);
                $table_notification = ApprovalTableNotificationMapper::fromStringToConstant($representation->notification_type);

                $this->table_factory->updateTable(
                    $table_status,
                    $table_notification,
                    $representation->reminder_occurence,
                    $representation->comment,
                    $representation->owner,
                );

                // Remove all reviewers
                foreach ($table->getReviewerArray() as $reviewer) {
                    $this->reviewer_factory->delUser($reviewer->getId());
                }
                // Before adding them
                $this->reviewer_factory->addUsers($representation->reviewers);
                foreach ($representation->reviewers_group_to_add as $user_group) {
                    $this->reviewer_factory->addUgroup($user_group);
                }
            });
        } catch (RestException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new I18NRestException(500, dgettext('tuleap-docman', 'Failed to update approval table'));
        }
    }
}
