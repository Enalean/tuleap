<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Tracker\Semantic\Timeframe\Administration;

use Tracker;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Numeric;

class SemanticTimeframeAdministrationPresenterBuilder
{
    /**
     * @var \Tracker_FormElementFactory
     */
    private $tracker_formelement_factory;

    public function __construct(\Tracker_FormElementFactory $tracker_formelement_factory)
    {
        $this->tracker_formelement_factory = $tracker_formelement_factory;
    }

    public function build(
        \CSRFSynchronizerToken $csrf,
        Tracker $tracker,
        string $target_url,
        ?Tracker_FormElement_Field_Date $start_date_field,
        ?Tracker_FormElement_Field_Numeric $duration_field
    ) : SemanticTimeframeAdministrationPresenter {
        $usable_date_fields = $this->buildSelectBoxEntries(
            $this->tracker_formelement_factory->getUsedFormElementsByType($tracker, ['date']),
            $start_date_field
        );

        $usable_numeric_fields = $this->buildSelectBoxEntries(
            $this->tracker_formelement_factory->getUsedFormElementsByType($tracker, ['int', 'float', 'computed']),
            $duration_field
        );

        return new SemanticTimeframeAdministrationPresenter(
            $csrf,
            $tracker,
            $target_url,
            $usable_date_fields,
            $usable_numeric_fields,
            $start_date_field,
            $duration_field
        );
    }

    private function buildSelectBoxEntries(array $fields, ?\Tracker_FormElement_Field $current_field) : array
    {
        return array_map(function (\Tracker_FormElement_Field $field) use ($current_field) {
            return [
                'id'          => $field->getId(),
                'label'       => $field->getLabel(),
                'is_selected' => $current_field && (int) $field->getId() === (int) $current_field->getId()
            ];
        }, $fields);
    }
}
