<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

use Tuleap\Tracker\REST\SemanticTimeframeWithEndDateRepresentation;

class TimeframeWithEndDate implements IComputeTimeframes
{
    private const NAME = 'timeframe-with-end-date';

    public static function getName(): string
    {
        return self::NAME;
    }

    /**
     * @var \Tracker_FormElement_Field_Date
     */
    private $start_date_field;
    /**
     * @var \Tracker_FormElement_Field_Date
     */
    private $end_date_field;

    public function __construct(
        \Tracker_FormElement_Field_Date $start_date_field,
        \Tracker_FormElement_Field_Date $end_date_field
    ) {
        $this->start_date_field = $start_date_field;
        $this->end_date_field   = $end_date_field;
    }

    public function isFieldUsed(\Tracker_FormElement_Field $field): bool
    {
        $field_id = $field->getId();

        return $field_id === $this->start_date_field->getId() ||
            $field_id === $this->end_date_field->getId();
    }

    public function getConfigDescription(): string
    {
        return \Codendi_HTMLPurifier::instance()
            ->purify(sprintf(
                dgettext('tuleap-tracker', 'Timeframe is based on start date field "%s" and end date field "%s".'),
                $this->start_date_field->getLabel(),
                $this->end_date_field->getLabel()
            ));
    }

    public function isDefined(): bool
    {
        return true;
    }

    public function exportToXML(\SimpleXMLElement $root, array $xml_mapping): void
    {
        $start_date_field_id = $this->start_date_field->getId();
        $start_date_ref      = array_search($start_date_field_id, $xml_mapping);
        $end_date_field_id   = $this->end_date_field->getId();
        $end_date_ref        = array_search($end_date_field_id, $xml_mapping);

        if (! $start_date_ref || ! $end_date_ref) {
            return;
        }

        $semantic = $root->addChild('semantic');
        $semantic->addAttribute('type', SemanticTimeframe::NAME);
        $semantic->addChild('start_date_field')->addAttribute('REF', $start_date_ref);
        $semantic->addChild('end_date_field')->addAttribute('REF', $end_date_ref);
    }

    public function exportToREST(\PFUser $user): ?IRepresentSemanticTimeframe
    {
        if (
            ! $this->start_date_field->userCanRead($user) ||
            ! $this->end_date_field->userCanRead($user)
        ) {
            return null;
        }

        return new SemanticTimeframeWithEndDateRepresentation(
            $this->start_date_field->getId(),
            $this->end_date_field->getId()
        );
    }

    public function save(\Tracker $tracker, SemanticTimeframeDao $dao): bool
    {
        return $dao->save(
            $tracker->getId(),
            $this->start_date_field->getId(),
            null,
            $this->end_date_field->getId()
        );
    }

    public function getStartDateField(): ?\Tracker_FormElement_Field_Date
    {
        return $this->start_date_field;
    }

    public function getEndDateField(): \Tracker_FormElement_Field_Date
    {
        return $this->end_date_field;
    }

    public function getDurationField(): ?\Tracker_FormElement_Field_Numeric
    {
        return null;
    }
}
