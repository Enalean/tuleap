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

namespace Tuleap\Taskboard\REST\v1\Cell;

use Luracast\Restler\RestException;
use PFUser;
use Tracker_ArtifactFactory;
use Tuleap\Tracker\REST\Helpers\IdsFromBodyAreNotUniqueException;
use Tuleap\Tracker\REST\Helpers\OrderIdOutOfBoundException;
use Tuleap\Tracker\REST\Helpers\OrderRepresentation;
use Tuleap\Tracker\REST\Helpers\OrderValidator;
use Tuleap\Tracker\REST\Helpers\ArtifactsRankOrderer;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Taskboard\Swimlane\SwimlaneChildrenRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use UserManager;

class CellPatcher
{
    /** @var UserManager */
    private $user_manager;
    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;
    /** @var SwimlaneChildrenRetriever */
    private $children_retriever;
    /** @var ArtifactsRankOrderer */
    private $rank_orderer;
    /** @var CardMappedFieldUpdater */
    private $mapped_field_updater;

    public function __construct(
        UserManager $user_manager,
        Tracker_ArtifactFactory $artifact_factory,
        SwimlaneChildrenRetriever $children_retriever,
        ArtifactsRankOrderer $rank_orderer,
        CardMappedFieldUpdater $mapped_field_updater,
    ) {
        $this->user_manager         = $user_manager;
        $this->artifact_factory     = $artifact_factory;
        $this->children_retriever   = $children_retriever;
        $this->rank_orderer         = $rank_orderer;
        $this->mapped_field_updater = $mapped_field_updater;
    }

    public static function build(): self
    {
        $artifact_factory = Tracker_ArtifactFactory::instance();
        return new CellPatcher(
            UserManager::instance(),
            $artifact_factory,
            new SwimlaneChildrenRetriever(),
            ArtifactsRankOrderer::build(),
            CardMappedFieldUpdater::build()
        );
    }

    /**
     * @throws I18NRestException
     * @throws RestException
     */
    public function patchCell(int $swimlane_id, int $column_id, CellPatchRepresentation $payload): void
    {
        $current_user      = $this->user_manager->getCurrentUser();
        $swimlane_artifact = $this->getSwimlaneArtifact($current_user, $swimlane_id);
        $project           = $swimlane_artifact->getTracker()->getProject();
        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt($project);

        $payload->checkIsValid();

        if ($payload->add !== null) {
            $artifact_to_add = $this->getArtifactToAdd($current_user, $payload->add);
            $this->mapped_field_updater->updateCardMappedField(
                $swimlane_artifact,
                $column_id,
                $artifact_to_add,
                $current_user
            );
        }

        $order = $payload->order;
        if ($order !== null) {
            $order->checkFormat();
            $this->validateOrder($order, $current_user, $swimlane_artifact);
            $this->rank_orderer->reorder($order, \Tracker_Artifact_PriorityHistoryChange::NO_CONTEXT, $project);
        }
    }

    /**
     * @throws I18NRestException
     */
    private function getArtifactToAdd(PFUser $current_user, int $id): Artifact
    {
        $artifact = $this->artifact_factory->getArtifactById($id);
        if (! $artifact || ! $artifact->userCanView($current_user)) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext('tuleap-taskboard', "Could not find artifact to add with id %d."),
                    $id
                )
            );
        }
        return $artifact;
    }

    /**
     * @throws RestException
     */
    private function getSwimlaneArtifact(PFUser $current_user, int $id): Artifact
    {
        $artifact = $this->artifact_factory->getArtifactById($id);
        if (! $artifact || ! $artifact->userCanView($current_user)) {
            throw new RestException(404);
        }

        return $artifact;
    }

    /**
     * @throws RestException
     */
    private function validateOrder(
        OrderRepresentation $order,
        PFUser $current_user,
        Artifact $swimlane_artifact,
    ): void {
        $children_artifact_ids          = $this->children_retriever->getSwimlaneArtifactIds(
            $swimlane_artifact,
            $current_user
        );
        $index_of_swimlane_children_ids = array_fill_keys($children_artifact_ids, true);
        $order_validator                = new OrderValidator($index_of_swimlane_children_ids);
        try {
            $order_validator->validate($order);
        } catch (IdsFromBodyAreNotUniqueException | OrderIdOutOfBoundException $e) {
            throw new RestException(400, $e->getMessage());
        }
    }
}
