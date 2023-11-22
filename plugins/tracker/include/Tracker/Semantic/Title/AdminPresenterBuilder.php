<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\Semantic\Title;

use CSRFSynchronizerToken;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Notifications\Settings\CheckEventShouldBeSentInNotification;

final class AdminPresenterBuilder
{
    public function __construct(
        private readonly Tracker_FormElementFactory $tracker_form_element_factory,
        private readonly CheckEventShouldBeSentInNotification $calendar_event_config,
    ) {
    }

    public function build(\Tracker_Semantic_Title $semantic_title, \Tracker $tracker, CSRFSynchronizerToken $csrf_token): AdminPresenter
    {
        $text_fields = $this->tracker_form_element_factory->getUsedTextFields($tracker);

        $possible_titles = [];
        foreach ($text_fields as $text_field) {
            if ($text_field->getId() === $semantic_title->getFieldId()) {
                $possible_titles[] = new PossibleFieldsForTitlePresenter($text_field->getId(), $text_field->getLabel(), true);
            } else {
                $possible_titles[] = new PossibleFieldsForTitlePresenter($text_field->getId(), $text_field->getLabel(), false);
            }
        }

        return new AdminPresenter(
            $semantic_title->getLabel(),
            $semantic_title->getUrl(),
            $csrf_token,
            $semantic_title->getFieldId() !== 0,
            $possible_titles,
            count($text_fields) > 0,
            TRACKER_BASE_URL . '/?tracker=' . urlencode((string) $tracker->getId()) . '&func=admin-semantic',
            $this->calendar_event_config->shouldSendEventInNotification($tracker->getId()),
        );
    }
}
