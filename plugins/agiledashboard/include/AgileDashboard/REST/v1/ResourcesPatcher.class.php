<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1;

use Tuleap\REST\v1\OrderRepresentationBase;
use Luracast\Restler\RestException;
use Tracker_ArtifactFactory;
use Tracker_Artifact_PriorityManager;
use PFUser;

class ResourcesPatcher {

    /**
     * @var Tracker_Artifact_PriorityManager
     */
    private $priority_manager;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var ArtifactLinkUpdater
     */
    private $artifactlink_updater;

    public function __construct(ArtifactLinkUpdater $artifactlink_updater, Tracker_ArtifactFactory $artifact_factory, Tracker_Artifact_PriorityManager $priority_manager) {
        $this->artifactlink_updater = $artifactlink_updater;
        $this->artifact_factory     = $artifact_factory;
        $this->priority_manager     = $priority_manager;
        $this->priority_manager->enableExceptionsOnError();
    }

    public function startTransaction() {
        $this->priority_manager->startTransaction();
    }

    public function commit() {
        $this->priority_manager->commit();
    }

    public function rollback() {
        $this->priority_manager->rollback();
    }

    public function updateArtifactPriorities(OrderRepresentationBase $order, $context, $project_id) {
        if ($order->direction === OrderRepresentationBase::BEFORE) {
            $this->priority_manager->moveListOfArtifactsBefore($order->ids, $order->compared_to, $context, $project_id);
        } else {
            $this->priority_manager->moveListOfArtifactsAfter($order->ids, $order->compared_to, $context, $project_id);
        }
    }

    public function removeArtifactFromSource(PFUser $user, array $add)
    {
        $to_add = array();
        foreach ($add as $move) {
            if (! isset($move['id']) || ! is_int($move['id'])) {
                throw new RestException(400, "invalid value specified for `id`. Expected: integer");
            }
            if (isset($move['remove_from']) && ! is_int($move['remove_from'])) {
                throw new RestException(400, "invalid value specified for `remove_from`. Expected: integer");
            }
            $to_add[] = $move['id'];
            if (isset($move['remove_from'])) {
                $from_artifact = $this->getArtifact($move['remove_from']);
                $this->artifactlink_updater->updateArtifactLinks(
                    $user,
                    $from_artifact,
                    array(),
                    array($move['id']),
                    \Tracker_FormElement_Field_ArtifactLink::NO_NATURE
                );
            }
        }

        return $to_add;
    }

    private function getArtifact($id) {
        $artifact = $this->artifact_factory->getArtifactById($id);

        if (! $artifact) {
            throw new RestException(404, 'Backlog Item not found');
        }

        return $artifact;
    }
}
