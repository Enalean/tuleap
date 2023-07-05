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

namespace Tuleap\Tracker\REST\Helpers;

use Luracast\Restler\RestException;
use Tuleap\Tracker\Artifact\Event\ArtifactsReordered;

class ArtifactsRankOrderer
{
    public function __construct(
        private readonly \Tracker_Artifact_PriorityManager $priority_manager,
        private readonly \EventManager $event_manager,
    ) {
        $this->priority_manager->enableExceptionsOnError();
    }

    public static function build(): self
    {
        return new self(
            new \Tracker_Artifact_PriorityManager(
                new \Tracker_Artifact_PriorityDao(),
                new \Tracker_Artifact_PriorityHistoryDao(),
                \UserManager::instance(),
                \Tracker_ArtifactFactory::instance()
            ),
            \EventManager::instance()
        );
    }

    /**
     * @throws RestException
     */
    public function reorder(OrderRepresentation $order, string $context_id, \Project $project): void
    {
        try {
            if ($order->direction === OrderRepresentation::BEFORE) {
                $this->priority_manager->moveListOfArtifactsBefore(
                    $order->ids,
                    $order->compared_to,
                    $context_id,
                    $project->getID()
                );
            } else {
                $this->priority_manager->moveListOfArtifactsAfter(
                    $order->ids,
                    $order->compared_to,
                    $context_id,
                    $project->getID()
                );
            }

            $this->event_manager->processEvent(new ArtifactsReordered($order->ids));
        } catch (\Tracker_Artifact_Exception_CannotRankWithMyself $e) {
            throw new RestException(400, $e->getMessage());
        }
    }
}
