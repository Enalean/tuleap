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

namespace Tuleap\Tracker\Semantic\Timeframe;

use Tuleap\Tracker\Semantic\Timeframe\Exceptions\FieldDoesNotBelongToTrackerException;
use Tuleap\Tracker\Semantic\Timeframe\Exceptions\FieldDoesNotHaveTheRightTypeException;
use Tuleap\Tracker\Semantic\Timeframe\Exceptions\FieldIdIsNotAnIntegerException;
use Tuleap\Tracker\Semantic\Timeframe\Exceptions\FieldStartDateMissingException;

class SemanticTimeframeUpdator
{
    /**
     * @var SemanticTimeframeDao
     */
    private $dao;

    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_factory;

    public function __construct(SemanticTimeframeDao $dao, \Tracker_FormElementFactory $form_factory)
    {
        $this->dao          = $dao;
        $this->form_factory = $form_factory;
    }

    public function update(\Tracker $tracker, \Codendi_Request $request) : void
    {
        try {
            $start_date_field_id = $this->getStartDateFieldIdFromRequest($tracker, $request);
            $duration_field_id   = $this->getDurationIdFromRequest($tracker, $request);

            $result = $this->dao->save(
                (int) $tracker->getId(),
                $start_date_field_id,
                $duration_field_id
            );

            if ($result === true) {
                $this->displayFeedbackSuccess();
            } else {
                $this->displayFeedbackError();
            }
        } catch (\Exception $exception) {
            $this->displayFeedbackError();
        }
    }

    /**
     * @throws FieldIdIsNotAnIntegerException
     */
    private function getNumericFieldIdFromRequest(\Codendi_Request $request, string $field_name) : int
    {
        $field_id = $request->get($field_name);

        if (! is_numeric($field_id)) {
            throw new \Exception('Field id is not an integer');
        }

        return (int) $field_id;
    }

    private function displayFeedbackSuccess() : void
    {
        $GLOBALS['Response']->addFeedback(
            \Feedback::INFO,
            dgettext('tuleap-tracker', 'Semantic timeframe updated successfully')
        );
    }

    private function displayFeedbackError() : void
    {
        $GLOBALS['Response']->addFeedback(
            \Feedback::ERROR,
            dgettext('tuleap-tracker', 'An error occurred while updating the timeframe semantic')
        );
    }

    private function getStartDateFieldIdFromRequest(\Tracker $tracker, \Codendi_Request $request) : int
    {
        $start_date_field_id = $this->getNumericFieldIdFromRequest($request, 'start-date-field-id');
        $start_date_field    = $this->form_factory->getUsedDateFieldById($tracker, $start_date_field_id);

        if ($start_date_field === null) {
            throw new \Exception('Field not found');
        }

        return $start_date_field_id;
    }

    private function getDurationIdFromRequest(\Tracker $tracker, \Codendi_Request $request) : int
    {
        $duration_field_id = $this->getNumericFieldIdFromRequest($request, 'duration-field-id');
        $duration_field    = $this->form_factory->getUsedFieldByIdAndType(
            $tracker,
            $duration_field_id,
            ['int', 'float', 'computed']
        );

        if (! $duration_field) {
            throw new \Exception('Field not found');
        }

        return $duration_field_id;
    }
}
