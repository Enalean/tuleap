<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Label\REST;

use ProjectHistoryDao;
use Tuleap\Label\Labelable;
use Tuleap\Label\LabelableDao;
use Tuleap\Project\Label\LabelDao;

class LabelsUpdater
{
    /**
     * @var LabelableDao
     */
    private $item_label_dao;

    /**
     * @var LabelDao
     */
    private $project_label_dao;

    /** @var string[] */
    private $new_labels;
    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;

    public function __construct(
        LabelDao $project_label_dao,
        LabelableDao $item_label_dao,
        ProjectHistoryDao $history_dao
    ) {
        $this->item_label_dao    = $item_label_dao;
        $this->project_label_dao = $project_label_dao;
        $this->history_dao       = $history_dao;
    }

    public function update($project_id, Labelable $item, LabelsPATCHRepresentation $body)
    {
        $this->new_labels = array();
        $this->project_label_dao->startTransaction();

        try {
            $array_of_label_ids_to_add    = $this->getLabelIdsToAdd($project_id, $body);
            $array_of_label_ids_to_remove = $this->getLabelIdsToRemove($body);
            if (array_intersect($array_of_label_ids_to_add, $array_of_label_ids_to_remove)) {
                throw new UnableToAddAndRemoveSameLabelException();
            }

            $this->project_label_dao->checkThatAllLabelIdsExistInProjectInTransaction($project_id, $array_of_label_ids_to_add);
            $this->item_label_dao->addLabelsInTransaction($item->getId(), $array_of_label_ids_to_add);
            $this->item_label_dao->removeLabelsInTransaction($item->getId(), $array_of_label_ids_to_remove);
            $this->project_label_dao->commit();
            foreach ($this->new_labels as $new_label) {
                $this->history_dao->groupAddHistory('label_created', $new_label, $project_id);
            }
        } catch (\Exception $exception) {
            $this->project_label_dao->rollBack();
            throw $exception;
        }
    }

    private function getOrCreateLabelId(LabelRepresentation $label_representation, $project_id)
    {
        if ($label_representation->id) {
            return $label_representation->id;
        }

        return $this->project_label_dao->createIfNeededInTransaction(
            $project_id,
            trim($label_representation->label),
            $this->new_labels
        );
    }

    private function getLabelIdsToRemove(LabelsPATCHRepresentation $body)
    {
        $labels_to_remove             = $body->remove ?: array();
        $array_of_label_ids_to_remove = array_map(
            static function (LabelRepresentation $label_representation) {
                return $label_representation->id;
            },
            $labels_to_remove
        );

        return $array_of_label_ids_to_remove;
    }

    private function getLabelIdsToAdd($project_id, LabelsPATCHRepresentation $body)
    {
        $labels_to_add = $body->add ?: array();
        $project_ids   = $labels_to_add ? array_fill(0, count($labels_to_add), $project_id) : array();

        $this->checkThatUserDoesNotTryToAddEmptyLabels($labels_to_add);

        $array_of_label_ids_to_add = array_map(
            function (LabelRepresentation $label_representation, $project_id) {
                return $this->getOrCreateLabelId($label_representation, $project_id);
            },
            $labels_to_add,
            $project_ids
        );

        return $array_of_label_ids_to_add;
    }

    private function checkThatUserDoesNotTryToAddEmptyLabels($labels_to_add)
    {
        $has_empty_label = array_reduce(
            $labels_to_add,
            function ($has_empty_label, LabelRepresentation $label_representation) {
                if ($has_empty_label) {
                    return true;
                }

                if ($label_representation->id) {
                    return false;
                }

                $name = trim($label_representation->label);

                return empty($name);
            },
            false
        );

        if ($has_empty_label) {
            throw new UnableToAddEmptyLabelException();
        }
    }
}
