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

namespace Tuleap\Tracker\Semantic\Progress;

use Tracker;
use Tracker_FormElement_Field_Numeric;
use Tuleap\Tracker\Semantic\Progress\Exceptions\SemanticProgressBrokenConfigurationException;

class SemanticProgressBuilder
{
    /**
     * @var SemanticProgressDao
     */
    private $dao;
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(
        SemanticProgressDao $dao,
        \Tracker_FormElementFactory $form_element_factory
    ) {
        $this->dao                  = $dao;
        $this->form_element_factory = $form_element_factory;
    }

    /**
     * @throws SemanticProgressBrokenConfigurationException
     */
    public function getSemantic(Tracker $tracker): SemanticProgress
    {
        $row = $this->dao->searchByTrackerId($tracker->getId());
        if ($row === null) {
            return new SemanticProgress($tracker, null);
        }

        $total_effort_field_id     = $row['total_effort_field_id'];
        $remaining_effort_field_id = $row['remaining_effort_field_id'];

        if ($total_effort_field_id !== null && $remaining_effort_field_id !== null) {
            return $this->buildEffortBasedSemanticProgress(
                $tracker,
                $total_effort_field_id,
                $remaining_effort_field_id
            );
        }

        return new SemanticProgress($tracker, null);
    }

    private function buildEffortBasedSemanticProgress(
        Tracker $tracker,
        int $total_effort_field_id,
        int $remaining_effort_field_id
    ): SemanticProgress {
        $total_effort_field = $this->form_element_factory->getUsedFieldByIdAndType(
            $tracker,
            $total_effort_field_id,
            ['int', 'float', 'computed']
        );

        $remaining_effort_field = $this->form_element_factory->getUsedFieldByIdAndType(
            $tracker,
            $remaining_effort_field_id,
            ['int', 'float', 'computed']
        );

        if ($total_effort_field === null || $remaining_effort_field === null) {
            throw new SemanticProgressBrokenConfigurationException($tracker);
        }

        if (
            $total_effort_field instanceof Tracker_FormElement_Field_Numeric &&
            $remaining_effort_field instanceof Tracker_FormElement_Field_Numeric
        ) {
            return new SemanticProgress(
                $tracker,
                new MethodBasedOnEffort(
                    $total_effort_field,
                    $remaining_effort_field
                )
            );
        }

        throw new SemanticProgressBrokenConfigurationException($tracker);
    }
}
