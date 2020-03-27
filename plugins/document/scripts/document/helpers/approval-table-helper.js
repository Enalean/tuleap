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

import {
    APPROVAL_APPROVED,
    APPROVAL_COMMENTED,
    APPROVAL_DECLINED,
    APPROVAL_NOT_YET,
    APPROVAL_REJECTED,
} from "../constants.js";

export function extractApprovalTableData(
    translated_states,
    approval_table,
    is_in_folder_content_row
) {
    let additional_class = "";
    if (is_in_folder_content_row) {
        additional_class = "document-tree-item-toggle-quicklook-approval-badge";
    }
    switch (approval_table) {
        case APPROVAL_NOT_YET:
            return {
                icon_badge: "fa-tlp-gavel-pending",
                badge_label: translated_states[APPROVAL_NOT_YET],
                badge_class: `tlp-badge-chrome-silver ${additional_class}`,
            };
        case APPROVAL_APPROVED:
            return {
                icon_badge: "fa-tlp-gavel-approved",
                badge_label: translated_states[APPROVAL_APPROVED],
                badge_class: `tlp-badge-success ${additional_class}`,
            };
        case APPROVAL_REJECTED:
            return {
                icon_badge: "fa-tlp-gavel-rejected",
                badge_label: translated_states[APPROVAL_REJECTED],
                badge_class: `tlp-badge-danger ${additional_class}`,
            };
        case APPROVAL_DECLINED:
            return {
                icon_badge: "fa-tlp-gavel-rejected",
                badge_label: translated_states[APPROVAL_DECLINED],
                badge_class: `tlp-badge-danger ${additional_class}`,
            };
        case APPROVAL_COMMENTED:
            return {
                icon_badge: "fa-tlp-gavel-comment",
                badge_label: translated_states[APPROVAL_COMMENTED],
                badge_class: `tlp-badge-info ${additional_class}`,
            };

        default:
            return {
                icon_badge: "fa-tlp-gavel-pending",
                badge_label: translated_states[APPROVAL_NOT_YET],
                badge_class: `tlp-badge-secondary ${additional_class}`,
            };
    }
}
