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

use Tuleap\Project\REST\ProjectReference;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;

/**
 * @psalm-immutable
 */
class AgileDashboard_CardRepresentation
{

    public const ROUTE = 'cards';

    /** @var string */
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

    /** @var String | null */
    public $accent_color;

    /** @var int | null */
    public $column_id;

    /** @var int[] */
    public $allowed_column_ids;

     /**
     * @var array Field values
     */
    public $values = [];

    private function __construct(
        string $id,
        string $label,
        ProjectReference $project,
        ArtifactReference $artifact,
        int $planning_id,
        string $status,
        ?string $accent_color,
        ?int $column_id,
        array $allowed_column_ids,
        array $values
    ) {
        $this->id                 = $id;
        $this->label              = $label;
        $this->project            = $project;
        $this->artifact           = $artifact;
        $this->planning_id        = $planning_id;
        $this->status             = $status;
        $this->accent_color       = $accent_color;
        $this->column_id          = $column_id;
        $this->allowed_column_ids = $allowed_column_ids;
        $this->values             = $values;
        $this->uri                = self::ROUTE . '/' . $this->id;
    }

    public static function build(Cardwall_CardInCellPresenter $card, $column_id, $planning_id, PFUser $user): self
    {
        $artifact = $card->getArtifact();

        $accent_color = null;
        if ($card->getCardPresenter()->getAccentColor()) {
            $accent_color = ColorHelper::CssRGBToHexa($card->getCardPresenter()->getAccentColor());
        }

        $column_id          = JsonCast::toInt($column_id);
        $allowed_column_ids = [];
        if ($column_id) {
            $allowed_column_ids = array_filter(
                array_map(
                    static function ($value) {
                        return JsonCast::toInt($value);
                    },
                    $card->getDropIntoIds()
                )
            );
        }

        $last_changeset = $artifact->getLastChangeset();
        $values         = [];
        if ($last_changeset !== null) {
            $values = self::mapAndFilter($card->getCardPresenter()->getFields(), self::getFieldsValuesFilter($user, $last_changeset));
        }

        return new self(
            $planning_id . '_' . $card->getId(),
            $artifact->getTitle(),
            new Tuleap\Project\REST\ProjectReference($artifact->getTracker()->getProject()),
            \Tuleap\Tracker\REST\Artifact\ArtifactReference::build($artifact),
            JsonCast::toInt($planning_id),
            self::getCardStatus($card),
            $accent_color,
            $column_id,
            $allowed_column_ids,
            $values,
        );
    }

      /**
     * Given a collection and a closure, apply on all elements, filter out the
     * empty results and normalize the array
     *
     * @param array $collection
     * @return array
     */
    private static function mapAndFilter(array $collection, Closure $function)
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

    private static function getFieldsValuesFilter(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        return function (Cardwall_CardFieldPresenter $field_presenter) use ($user, $changeset) {
            if ($field_presenter->getTrackerField()->userCanRead($user)) {
                return $field_presenter->getTrackerField()->getRESTValue($user, $changeset);
            }
            return false;
        };
    }

    private static function getCardStatus(Cardwall_CardInCellPresenter $card)
    {
        $semantic = Tracker_Semantic_Status::load($card->getArtifact()->getTracker());

        return $semantic->getNormalizedStatusLabel($card->getArtifact());
    }
}
