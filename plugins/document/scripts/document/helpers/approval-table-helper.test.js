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

import { extractApprovalTableData } from "./approval-table-helper.js";
import {
    APPROVAL_APPROVED,
    APPROVAL_COMMENTED,
    APPROVAL_DECLINED,
    APPROVAL_NOT_YET,
    APPROVAL_REJECTED,
} from "../constants.js";

describe("extractApprovalTableData", () => {
    let translated_states;
    beforeEach(() => {
        translated_states = {
            "Not yet": "Not yet",
            Approved: "Approved",
            Rejected: "Rejected",
            Declined: "Declined",
            Commented: "Commented",
        };
    });

    it("Given approval status is not yet it should returns the corresponding badge information", () => {
        const expected_badge = {
            icon_badge: "fa-tlp-gavel-pending",
            badge_label: translated_states[APPROVAL_NOT_YET],
            badge_class: `tlp-badge-chrome-silver `,
        };

        const badge = extractApprovalTableData(translated_states, APPROVAL_NOT_YET, false);
        expect(expected_badge).toEqual(badge);
    });

    it("Given approval status is approved it should returns the corresponding badge information", () => {
        const expected_badge = {
            icon_badge: "fa-tlp-gavel-approved",
            badge_label: translated_states[APPROVAL_APPROVED],
            badge_class: `tlp-badge-success `,
        };
        const badge = extractApprovalTableData(translated_states, APPROVAL_APPROVED, false);
        expect(expected_badge).toEqual(badge);
    });

    it("Given approval status is rejected it should returns the corresponding badge information", () => {
        const expected_badge = {
            icon_badge: "fa-tlp-gavel-rejected",
            badge_label: translated_states[APPROVAL_REJECTED],
            badge_class: `tlp-badge-danger `,
        };
        const badge = extractApprovalTableData(translated_states, APPROVAL_REJECTED, false);
        expect(expected_badge).toEqual(badge);
    });

    it("Given approval status is declined it should returns the corresponding badge information", () => {
        const expected_badge = {
            icon_badge: "fa-tlp-gavel-rejected",
            badge_label: translated_states[APPROVAL_DECLINED],
            badge_class: `tlp-badge-danger `,
        };
        const badge = extractApprovalTableData(translated_states, APPROVAL_DECLINED, false);
        expect(expected_badge).toEqual(badge);
    });

    it("Given approval status is commented it should returns the corresponding badge information", () => {
        const expected_badge = {
            icon_badge: "fa-tlp-gavel-comment",
            badge_label: translated_states[APPROVAL_COMMENTED],
            badge_class: `tlp-badge-info `,
        };
        const badge = extractApprovalTableData(translated_states, APPROVAL_COMMENTED, false);
        expect(expected_badge).toEqual(badge);
    });

    it("Given additional classes should be added to the badge, the the badge have it", () => {
        const expected_badge = {
            icon_badge: "fa-tlp-gavel-approved",
            badge_label: translated_states[APPROVAL_APPROVED],
            badge_class: `tlp-badge-success document-tree-item-toggle-quicklook-approval-badge`,
        };
        const badge = extractApprovalTableData(translated_states, APPROVAL_APPROVED, true);
        expect(expected_badge).toEqual(badge);
    });
});
