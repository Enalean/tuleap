<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Campaign;

use CSRFSynchronizerToken;
use PFUser;
use Tuleap\Tracker\Semantic\Status\SemanticStatusNotDefinedException;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;

class StatusUpdater
{
    public const STATUS_CHANGE_CLOSED_VALUE = 'closed';
    public const STATUS_CHANGE_OPEN_VALUE   = 'open';

    /**
     * @var StatusValueRetriever
     */
    private $status_value_retriever;

    public function __construct(StatusValueRetriever $status_value_retriever)
    {
        $this->status_value_retriever = $status_value_retriever;
    }

    public function openCampaign(
        Campaign $campaign,
        PFUser $user,
        CSRFSynchronizerToken $csrf_token,
    ): void {
        $this->updateCampaignStatus(
            $campaign,
            $user,
            $csrf_token,
            self::STATUS_CHANGE_OPEN_VALUE
        );
    }

    public function closeCampaign(
        Campaign $campaign,
        PFUser $user,
        CSRFSynchronizerToken $csrf_token,
    ): void {
        $this->updateCampaignStatus(
            $campaign,
            $user,
            $csrf_token,
            self::STATUS_CHANGE_CLOSED_VALUE
        );
    }

    /**
     * @throws SemanticStatusNotDefinedException
     */
    public function updateCampaignStatus(
        Campaign $campaign,
        PFUser $user,
        CSRFSynchronizerToken $csrf_token,
        string $change_status,
    ): void {
        $csrf_token->check();

        $artifact = $campaign->getArtifact();
        $tracker  = $artifact->getTracker();

        $status_field = $tracker->getStatusField();
        if ($status_field === null) {
            throw new SemanticStatusNotDefinedException();
        }

        if ($change_status === self::STATUS_CHANGE_CLOSED_VALUE) {
            $value = $this->status_value_retriever->getFirstClosedValueUserCanRead($tracker, $user);
        } else {
            $value = $this->status_value_retriever->getFirstOpenValueUserCanRead($tracker, $user);
        }

        $fields_data = [
            $status_field->getId() => $status_field->getFieldData($value->getLabel()),
        ];

        $artifact->createNewChangeset(
            $fields_data,
            "",
            $user
        );
    }
}
