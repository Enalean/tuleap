/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import type { BacklogItem, TestDefinition } from "../../type";

export function buildCreateNewTestDefinitionLink(
    testdefinition_tracker_id: number,
    milestone_id: number,
    backlog_item: BacklogItem,
): string {
    const url_params = new URLSearchParams({
        tracker: String(testdefinition_tracker_id),
        func: "new-artifact",
        ttm_backlog_item_id: String(backlog_item.id),
        ttm_milestone_id: String(milestone_id),
    });

    return `/plugins/tracker/?${url_params.toString()}`;
}

export function buildEditBacklogItemLink(milestone_id: number, backlog_item: BacklogItem): string {
    return buildEditArtifactLink(backlog_item.id, backlog_item.id, milestone_id);
}

export function buildEditTestDefinitionItemLink(
    milestone_id: number,
    test_definition: TestDefinition,
    backlog_item: BacklogItem,
): string {
    return buildEditArtifactLink(test_definition.id, backlog_item.id, milestone_id);
}

function buildEditArtifactLink(
    id: number,
    ttm_backlog_item_id: number,
    ttm_milestone_id: number,
): string {
    const url_params = new URLSearchParams({
        aid: String(id),
        ttm_backlog_item_id: String(ttm_backlog_item_id),
        ttm_milestone_id: String(ttm_milestone_id),
    });

    return `/plugins/tracker/?${url_params.toString()}`;
}

export function buildGoToTestExecutionLink(
    project_id: number,
    milestone_id: number,
    test_definition: TestDefinition,
): string | null {
    if (test_definition.test_status === null) {
        return null;
    }

    return `/plugins/testmanagement/?group_id=${encodeURIComponent(
        project_id,
    )}&milestone_id=${encodeURIComponent(milestone_id)}#!/campaigns/${encodeURIComponent(
        test_definition.test_campaign_defining_status.id,
    )}/${encodeURIComponent(
        test_definition.test_execution_used_to_define_status.id,
    )}/${encodeURIComponent(test_definition.id)}`;
}
