<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use Tuleap\REST\JsonCast;

class AgileDashboard_CardRepresentation
{

    public const ROUTE = 'cards';

    /** @var int */
    public $id;

    /** @var String */
    public $label;

    /** @var String */
    public $uri;

    /** @var ProjectReference */
    public $project;

    /** @var ArtifactReference */
    public $artifact;

    /** @var int */
    public $planning_id;

    /** @var String */
    public $status;

    /** @var String */
    public $accent_color;

    /** @var int */
    public $column_id;

    /** @var int[] */
    public $allowed_column_ids;

     /**
     * @var array Field values
     */
    public $values = array();

    public function build(Cardwall_CardInCellPresenter $card, $column_id, $planning_id, PFUser $user)
    {
        $this->id           = $planning_id . '_' . $card->getId();
        $this->label        = $card->getArtifact()->getTitle();
        $this->uri          = self::ROUTE . '/' . $this->id;
        $artifact           = $card->getArtifact();
        $this->project      = $this->getProjectReference($artifact->getTracker()->getProject());
        $this->artifact     = $this->getArtifactReference($artifact);

        $this->planning_id  = JsonCast::toInt($planning_id);
        $this->status       = $this->getCardStatus($card);
        if ($card->getCardPresenter()->getAccentColor()) {
            $this->accent_color = ColorHelper::CssRGBToHexa($card->getCardPresenter()->getAccentColor());
        }
        $this->column_id    = JsonCast::toInt($column_id);
        if ($this->column_id) {
            $this->allowed_column_ids = array_filter(
                array_map(
                    static function ($value) {
                        return JsonCast::toInt($value);
                    },
                    $card->getDropIntoIds()
                )
            );
        } else {
            $this->allowed_column_ids = array();
        }

        $last_changeset = $artifact->getLastChangeset();
        if ($last_changeset !== null) {
            $this->values = $this->mapAndFilter($card->getCardPresenter()->getFields(), $this->getFieldsValuesFilter($user, $last_changeset));
        }
    }

    private function getProjectReference(Project $project)
    {
        $project_reference = new Tuleap\Project\REST\ProjectReference();
        $project_reference->build($project);

        return $project_reference;
    }

    private function getArtifactReference(Tracker_Artifact $artifact)
    {
        $artifact_reference = new \Tuleap\Tracker\REST\Artifact\ArtifactReference();
        $artifact_reference->build($artifact);

        return $artifact_reference;
    }

      /**
     * Given a collection and a closure, apply on all elements, filter out the
     * empty results and normalize the array
     *
     * @param array $collection
     * @return array
     */
    private function mapAndFilter(array $collection, Closure $function)
    {
        return array_values(
            array_filter(
                array_map(
                    $function,
                    $collection
                )
            )
        );
    }

    private function getFieldsValuesFilter(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        return function (Cardwall_CardFieldPresenter $field_presenter) use ($user, $changeset) {
            if ($field_presenter->getTrackerField()->userCanRead($user)) {
                return $field_presenter->getTrackerField()->getRESTValue($user, $changeset);
            }
            return false;
        };
    }

    private function getCardStatus(Cardwall_CardInCellPresenter $card)
    {
        $semantic = Tracker_Semantic_Status::load($card->getArtifact()->getTracker());

        return $semantic->getNormalizedStatusLabel($card->getArtifact());
    }
}
