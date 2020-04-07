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

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Timeframe;

use Codendi_Request;
use Exception;
use Tracker;

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
        $this->dao = $dao;
        $this->form_factory = $form_factory;
    }

    public function update(Tracker $tracker, Codendi_Request $request): void
    {
        try {
            $start_date_field_id = $this->getNumericFieldIdFromRequest($request, 'start-date-field-id');
            $duration_field_id   = $this->getNumericFieldIdFromRequest($request, 'duration-field-id');
            $end_date_field_id   = $this->getNumericFieldIdFromRequest($request, 'end-date-field-id');

            if (! $this->requestIsCorrect($tracker, $start_date_field_id, $duration_field_id, $end_date_field_id)) {
                $this->displayFeedbackError();
                return;
            }

            $result = $this->dao->save(
                (int) $tracker->getId(),
                $start_date_field_id,
                $duration_field_id,
                $end_date_field_id
            );

            if ($result === true) {
                $this->displayFeedbackSuccess();
            } else {
                $this->displayFeedbackError();
            }
        } catch (TimeframeStartDateAndEndDateAreTheSameFieldException $exception) {
            $this->displayStartDateAndEndDateAreTheSameFieldFeedbackError();
        } catch (Exception $exception) {
            $this->displayFeedbackError();
        }
    }

    private function getNumericFieldIdFromRequest(Codendi_Request $request, string $field_name): ?int
    {
        $field_id = $request->get($field_name);

        if (! $field_id) {
            return null;
        }

        if (! is_numeric($field_id)) {
            throw new Exception('Field id is not an integer');
        }

        return (int) $field_id;
    }

    private function displayFeedbackSuccess(): void
    {
        $GLOBALS['Response']->addFeedback(
            \Feedback::INFO,
            dgettext('tuleap-tracker', 'Semantic timeframe updated successfully')
        );
    }

    private function displayFeedbackError(): void
    {
        $GLOBALS['Response']->addFeedback(
            \Feedback::ERROR,
            dgettext('tuleap-tracker', 'An error occurred while updating the timeframe semantic')
        );
    }

    private function displayStartDateAndEndDateAreTheSameFieldFeedbackError(): void
    {
        $GLOBALS['Response']->addFeedback(
            \Feedback::ERROR,
            dgettext('tuleap-tracker', 'The start date field and the end date field cannot be the same.')
        );
    }

    private function startDateFieldIdIsCorrect(Tracker $tracker, int $start_date_field_id): bool
    {
        $start_date_field = $this->form_factory->getUsedDateFieldById($tracker, $start_date_field_id);

        return $start_date_field !== null;
    }

    private function durationFieldIdIsCorrect(Tracker $tracker, int $duration_field_id): bool
    {
        $duration_field = $this->form_factory->getUsedFieldByIdAndType(
            $tracker,
            $duration_field_id,
            ['int', 'float', 'computed']
        );

        return $duration_field !== null;
    }

    private function endDateFieldIdIsCorrect(Tracker $tracker, int $end_date_field_id): bool
    {
        $end_date_field = $this->form_factory->getUsedDateFieldById($tracker, $end_date_field_id);

        return $end_date_field !== null;
    }

    /**
     * @psalm-assert-if-true !null $start_date_field_id
     */
    private function requestIsCorrect(Tracker $tracker, ?int $start_date_field_id, ?int $duration_field_id, ?int $end_date_field_id): bool
    {
        if ($start_date_field_id === null || ! $this->startDateFieldIdIsCorrect($tracker, $start_date_field_id)) {
            return false;
        }

        if ($duration_field_id === null && $end_date_field_id === null) {
            return false;
        }

        if ($duration_field_id !== null && $end_date_field_id !== null) {
            return false;
        }

        if ($duration_field_id !== null) {
            return $this->durationFieldIdIsCorrect($tracker, $duration_field_id);
        }

        $this->checkStartDateFieldIsDifferentOfEndDateField($start_date_field_id, $end_date_field_id);

        return $this->endDateFieldIdIsCorrect($tracker, $end_date_field_id);
    }

    /**
     * @throws TimeframeStartDateAndEndDateAreTheSameFieldException
     */
    private function checkStartDateFieldIsDifferentOfEndDateField(?int $start_date_field_id, ?int $end_date_field_id): void
    {
        if ($start_date_field_id === null || $end_date_field_id === null) {
            return;
        }

        if ($start_date_field_id === $end_date_field_id) {
            throw new TimeframeStartDateAndEndDateAreTheSameFieldException();
        }
    }
}
