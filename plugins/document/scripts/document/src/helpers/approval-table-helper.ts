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
    ApprovalTableReviewer,
    ApprovalTable,
    DefaultFileItem,
    Embedded,
    Empty,
    FakeItem,
    Item,
    ItemFile,
    Link,
    Wiki,
} from "../type";
import { isEmbedded, isEmpty, isFile, isLink, isOtherType, isWiki } from "./type-check-helper";

export interface ApprovalTableBadge {
    icon_badge: string;
    badge_label: string;
    badge_class: string;
}

export function isAnApprovableDocument(
    item: Item | Embedded | Empty | ItemFile | Link | Wiki | FakeItem | DefaultFileItem,
): item is ApprovableDocument {
    return !isEmpty(item) && !isOtherType(item);
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

export function translateReviewStatus(status: string, $gettext: (msg: string) => string): string {
    switch (status) {
        case "not_yet":
            return $gettext("Not yet");
        case "approved":
            return $gettext("Approved");
        case "rejected":
            return $gettext("Rejected");
        case "comment_only":
            return $gettext("Commented");
        case "will_not_review":
            return $gettext("Declined");
        default:
            throw Error("Unknown review status " + status);
    }
}

export function translateNotificationType(type: string, $gettext: (msg: string) => string): string {
    switch (type) {
        case "disabled":
            return $gettext("Disabled");
        case "all_at_once":
            return $gettext("All at once");
        case "sequential":
            return $gettext("Sequential");
        default:
            throw Error("Unknown notification type " + type);
    }
}

export function rearrangeReviewersTable(
    current_value: ReadonlyArray<ApprovalTableReviewer>,
    updated_reviewer: ApprovalTableReviewer,
    new_rank: number,
): Array<ApprovalTableReviewer> {
    const result: Array<ApprovalTableReviewer> = [];

    // min/max on [0;current_value.length[
    const used_new_rank =
        new_rank < 0 ? 0 : new_rank >= current_value.length ? current_value.length - 1 : new_rank;

    let index = 0;
    current_value.forEach((reviewer) => {
        if (reviewer.user.id === updated_reviewer.user.id) {
            return;
        }
        if (index === used_new_rank) {
            result.push({
                ...updated_reviewer,
                rank: index,
            });
            index++;
        }
        result.push({
            ...reviewer,
            rank: index,
        });
        index++;
    });

    if (result.length === current_value.length - 1 && used_new_rank === current_value.length - 1) {
        // Edge case of above loop:
        // When reviewer is moved at the end, `index === used_new_rank` is never satisfied as we skipped updated_reviewer iteration
        result.push({
            ...updated_reviewer,
            rank: used_new_rank,
        });
    }

    return result;
}

export function isItemVersionable(item: Item): boolean {
    return isEmbedded(item) || isFile(item) || isWiki(item) || isLink(item);
}

export function isTableLinkedToLastItemVersion(
    item: ApprovableDocument,
    table: ApprovalTable,
): boolean {
    if (!isItemVersionable(item)) {
        return true;
    }

    if (isEmbedded(item)) {
        return item.embedded_file_properties?.version_number === table.version_number;
    }

    if (isFile(item)) {
        return item.file_properties?.version_number === table.version_number;
    }

    if (isWiki(item)) {
        return item.wiki_properties.version_number === table.version_number;
    }

    if (isLink(item)) {
        return item.link_properties.version_number === table.version_number;
    }

    throw Error(`Item type ${item.type} is not versionable nor approvable`);
}
