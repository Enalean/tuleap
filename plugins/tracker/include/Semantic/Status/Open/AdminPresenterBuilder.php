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

namespace Tuleap\Tracker\Semantic\Status\Open;

use CSRFSynchronizerToken;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneDao;

final class AdminPresenterBuilder
{
    public function __construct(private Tracker_FormElementFactory $tracker_form_element_factory, private SemanticDoneDao $semantic_done_dao)
    {
    }

    public function build(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus $semantic_status, \Tracker $tracker, CSRFSynchronizerToken $csrf_token): AdminPresenter
    {
        $list_fields = $this->tracker_form_element_factory->getUsedListFields($tracker);

        $possible_status_field = [];
        foreach ($list_fields as $list_field) {
            $is_selected             = $list_field->getId() === $semantic_status->getFieldId();
            $possible_status_field[] = new PossibleFieldsForStatusPresenter($list_field->getId(), $list_field->getLabel(), $is_selected);
        }

        $status_values = [];
        $field_status  = $semantic_status->getField();
        if ($field_status) {
            $disabled_values = $this->getDisabledValues($tracker);
            foreach ($field_status->getAllVisibleValues() as $visible_value) {
                $status_values[] = new StatusValuePresenter(
                    $visible_value->getId(),
                    $visible_value->getLabel(),
                    in_array($visible_value->getId(), $semantic_status->getOpenValues()),
                    in_array($visible_value->getId(), $disabled_values),
                );
            }
        }

        $selected_values = $this->semantic_done_dao->getSelectedValues($tracker->getId());

        return new AdminPresenter(
            $semantic_status->getFieldId(),
            $semantic_status->getLabel(),
            $semantic_status->getUrl(),
            $csrf_token,
            count($possible_status_field) > 0,
            $possible_status_field,
            $semantic_status->getFieldId() !== 0,
            $status_values,
            TRACKER_BASE_URL . '/?tracker=' . urlencode((string) $tracker->getId()) . '&func=admin-semantic',
            count($selected_values) > 0,
            $semantic_status->getField()?->getLabel()
        );
    }

    private function getDisabledValues(\Tracker $tracker): array
    {
        $disabled_values = [];
        foreach ($this->semantic_done_dao->getSelectedValues($tracker->getId()) as $value_row) {
            $disabled_values[] = $value_row['value_id'];
        }

        return $disabled_values;
    }
}
