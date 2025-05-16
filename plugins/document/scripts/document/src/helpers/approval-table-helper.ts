/*
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

import { APPROVAL_APPROVED, APPROVAL_NOT_YET, APPROVAL_REJECTED } from "../constants";
import type {
    ApprovableDocument,
    DefaultFileItem,
    Embedded,
    Empty,
    FakeItem,
    Item,
    ItemFile,
    Link,
    Wiki,
} from "../type";

export interface ApprovalTableBadge {
    icon_badge: string;
    badge_label: string;
    badge_class: string;
}

export function hasAnApprovalTable(
    item: Item | Embedded | Empty | ItemFile | Link | Wiki | FakeItem | DefaultFileItem,
): item is ApprovableDocument {
    return "has_approval_table" in item && item.approval_table !== null;
}

export function extractApprovalTableData(
    translated_approval_states_map: Map<string, string>,
    approval_table_state: string,
    is_in_folder_content_row: boolean,
): ApprovalTableBadge {
    let additional_class = "";
    if (is_in_folder_content_row) {
        additional_class = "document-tree-item-toggle-quicklook-approval-badge";
    }

    const state = translated_approval_states_map.get(approval_table_state);

    switch (state) {
        case APPROVAL_NOT_YET:
            return {
                icon_badge: "fa-tlp-gavel-pending",
                badge_label: approval_table_state,
                badge_class: `tlp-badge-secondary ${additional_class}`,
            };
        case APPROVAL_APPROVED:
            return {
                icon_badge: "fa-tlp-gavel-approved",
                badge_label: approval_table_state,
                badge_class: `tlp-badge-success ${additional_class}`,
            };
        case APPROVAL_REJECTED:
            return {
                icon_badge: "fa-tlp-gavel-rejected",
                badge_label: approval_table_state,
                badge_class: `tlp-badge-danger ${additional_class}`,
            };
        default:
            return {
                icon_badge: "fa-tlp-gavel-pending",
                badge_label: approval_table_state,
                badge_class: `tlp-badge-secondary ${additional_class}`,
            };
    }
}
