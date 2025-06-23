<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Description;

use CSRFSynchronizerToken;
use Tracker_FormElementFactory;

final class AdminPresenterBuilder
{
    public function __construct(private Tracker_FormElementFactory $tracker_form_element_factory)
    {
    }

    public function build(\Tuleap\Tracker\Semantic\Description\TrackerSemanticDescription $semantic_description, \Tuleap\Tracker\Tracker $tracker, CSRFSynchronizerToken $csrf_token): AdminPresenter
    {
        $text_fields = $this->tracker_form_element_factory->getUsedFormElementsByType($tracker, ['text']);

        $possible_descriptions = [];
        foreach ($text_fields as $text_field) {
            $is_selected             = $text_field->getId() === $semantic_description->getFieldId();
            $possible_descriptions[] = new PossibleFieldsForDescriptionPresenter($text_field->getId(), $text_field->getLabel(), $is_selected);
        }

        return new AdminPresenter(
            $semantic_description->getLabel(),
            $semantic_description->getUrl(),
            $csrf_token,
            $semantic_description->getFieldId() !== 0,
            $possible_descriptions,
            count($text_fields) > 0,
            TRACKER_BASE_URL . '/?tracker=' . urlencode((string) $tracker->getId()) . '&func=admin-semantic'
        );
    }
}
