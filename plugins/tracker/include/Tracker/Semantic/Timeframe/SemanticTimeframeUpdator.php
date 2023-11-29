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
use Tuleap\Tracker\Notifications\Settings\CheckEventShouldBeSentInNotification;

class SemanticTimeframeUpdator
{
    public function __construct(
        private readonly SemanticTimeframeDao $dao,
        private readonly \Tracker_FormElementFactory $form_factory,
        private readonly SemanticTimeframeSuitableTrackersOtherSemanticsCanBeImpliedFromRetriever $suitable_trackers_retriever,
        private readonly CheckEventShouldBeSentInNotification $calendar_event_config,
    ) {
    }

    public function update(Tracker $tracker, Codendi_Request $request): void
    {
        try {
            $start_date_field_id     = $this->getNumericFieldIdFromRequest($request, 'start-date-field-id');
            $duration_field_id       = $this->getNumericFieldIdFromRequest($request, 'duration-field-id');
            $end_date_field_id       = $this->getNumericFieldIdFromRequest($request, 'end-date-field-id');
            $implied_from_tracker_id = $this->getNumericFieldIdFromRequest($request, 'implied-from-tracker-id');

            if (! $this->requestIsCorrect($tracker, $start_date_field_id, $duration_field_id, $end_date_field_id, $implied_from_tracker_id)) {
                $this->displayFeedbackError();
                return;
            }

            $result = $this->dao->save(
                $tracker->getId(),
                $start_date_field_id,
                $duration_field_id,
                $end_date_field_id,
                $implied_from_tracker_id
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

    public function reset(Tracker $tracker): void
    {
        $configs_relying_on_current_tracker = $this->dao->getSemanticsImpliedFromGivenTracker($tracker->getId());
        if (! $configs_relying_on_current_tracker) {
            $this->dao->deleteTimeframeSemantic($tracker->getId());

            $GLOBALS['Response']->addFeedback(
                \Feedback::INFO,
                dgettext('tuleap-tracker', 'Semantic timeframe reset successfully')
            );

            return;
        }

        $GLOBALS['Response']->addFeedback(
            \Feedback::ERROR,
            dgettext('tuleap-tracker', 'You cannot reset this semantic because some trackers inherit their own semantic timeframe from this one.')
        );
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

    private function requestIsCorrect(
        Tracker $tracker,
        ?int $start_date_field_id,
        ?int $duration_field_id,
        ?int $end_date_field_id,
        ?int $implied_from_tracker_id,
    ): bool {
        if ($implied_from_tracker_id !== null && ! $start_date_field_id && ! $duration_field_id && ! $end_date_field_id) {
            return ! $this->calendar_event_config->shouldSendEventInNotification($tracker->getId()) &&
                $this->isTrackerSuitableToImplyTheCurrentSemanticFromIt($tracker, $implied_from_tracker_id);
        }

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

    private function isTrackerSuitableToImplyTheCurrentSemanticFromIt(Tracker $tracker, int $implied_from_tracker_id): bool
    {
        return array_key_exists(
            $implied_from_tracker_id,
            $this->suitable_trackers_retriever->getTrackersWeCanUseToImplyTheSemanticOfTheCurrentTrackerFrom($tracker)
        );
    }
}
