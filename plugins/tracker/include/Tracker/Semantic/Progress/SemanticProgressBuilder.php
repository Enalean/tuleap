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

    public function getSemantic(Tracker $tracker): SemanticProgress
    {
        $row = $this->dao->searchByTrackerId($tracker->getId());
        if ($row === null) {
            return $this->getUnconfiguredSemanticProgress($tracker);
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

        return $this->getInvalidSemanticProgress(
            $tracker,
            dgettext('tuleap-tracker', 'Progress semantic is not properly configured.')
        );
    }

    private function buildEffortBasedSemanticProgress(
        Tracker $tracker,
        int $total_effort_field_id,
        int $remaining_effort_field_id
    ): SemanticProgress {
        if ($total_effort_field_id === $remaining_effort_field_id) {
            return $this->getInvalidSemanticProgress(
                $tracker,
                dgettext(
                    'tuleap-tracker',
                    'Progress semantic is not properly configured: total effort and remaining effort fields have to be two different fields.'
                )
            );
        }

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

        if (! $total_effort_field instanceof Tracker_FormElement_Field_Numeric) {
            return $this->getInvalidSemanticProgress(
                $tracker,
                dgettext('tuleap-tracker', 'Progress semantic is not properly configured: unable to find the total effort field.')
            );
        }

        if (! $remaining_effort_field instanceof Tracker_FormElement_Field_Numeric) {
            return $this->getInvalidSemanticProgress(
                $tracker,
                dgettext('tuleap-tracker', 'Progress semantic is not properly configured: unable to find the remaining effort field.')
            );
        }

        return new SemanticProgress(
            $tracker,
            new MethodBasedOnEffort(
                $this->dao,
                $total_effort_field,
                $remaining_effort_field
            )
        );
    }

    private function getUnconfiguredSemanticProgress(Tracker $tracker): SemanticProgress
    {
        return new SemanticProgress(
            $tracker,
            new MethodNotConfigured()
        );
    }

    private function getInvalidSemanticProgress(Tracker $tracker, string $error_message): SemanticProgress
    {
        return new SemanticProgress(
            $tracker,
            new InvalidMethod($error_message),
        );
    }
}
