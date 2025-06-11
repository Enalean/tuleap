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

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Contributor;

final class AdminPresenterBuilder
{
    public function __construct(private \Tracker_FormElementFactory $tracker_form_element_factory)
    {
    }

    public function build(\Tuleap\Tracker\Semantic\Contributor\TrackerSemanticContributor $semantic_contributor, \Tracker $tracker, \CSRFSynchronizerToken $csrf_token): AdminPresenter
    {
        $list_user_fields = $this->tracker_form_element_factory->searchUsedUserClosedListFields($tracker);

        $possible_contributors = [];
        foreach ($list_user_fields as $list_user_field) {
            $is_selected             = $list_user_field->getId() === $semantic_contributor->getFieldId();
            $possible_contributors[] = new PossibleFieldsForContributorPresenter(
                $list_user_field->getId(),
                $list_user_field->getLabel(),
                $is_selected,
            );
        }

        return new AdminPresenter(
            $semantic_contributor->getLabel(),
            $semantic_contributor->getUrl(),
            $csrf_token,
            $semantic_contributor->getFieldId() !== 0,
            $possible_contributors,
            count($list_user_fields) > 0,
            TRACKER_BASE_URL . '/?tracker=' . urlencode((string) $tracker->getId()) . '&func=admin-semantic'
        );
    }
}
