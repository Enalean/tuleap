<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Semantic;

use CSRFSynchronizerToken;

final class InitialEffortSemanticAdminPresenterBuilder
{
    public function __construct(private SemanticInitialEffortPossibleFieldRetriever $possible_field_retriever)
    {
    }

    public function build(\AgileDashBoard_Semantic_InitialEffort $initial_effort, CSRFSynchronizerToken $token): InitialEffortAdminSemanticPresenter
    {
        $tracker        = $initial_effort->getTracker();
        $numeric_fields = $this->possible_field_retriever->getPossibleFieldsForInitialEffort(
            $tracker,
            $initial_effort->getFieldId(),
        );

        $admin_tracker_url = TRACKER_BASE_URL . '/?tracker=' . $tracker->getId() . '&func=admin-semantic';

        return new InitialEffortAdminSemanticPresenter(
            $token,
            $admin_tracker_url,
            PossibleFieldsPresenter::buildFromTrackerFieldList($numeric_fields, $initial_effort),
            $initial_effort->getUrl(),
            $initial_effort->getFieldId() !== 0
        );
    }
}
