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

import { describe, expect, it } from "vitest";
import type { ApprovalTableBadge } from "./approval-table-helper";
import { extractApprovalTableData, hasAnApprovalTable } from "./approval-table-helper";
import { APPROVAL_APPROVED, APPROVAL_NOT_YET, APPROVAL_REJECTED } from "../constants";
import type { Folder, ItemFile } from "../type";

describe("extractApprovalTableData", () => {
    const translated_states = new Map();
    translated_states.set("Not yet", APPROVAL_NOT_YET);
    translated_states.set("Approved", APPROVAL_APPROVED);
    translated_states.set("Rejected", APPROVAL_REJECTED);

    it("Given approval status is not yet it should returns the corresponding badge information", () => {
        const expected_badge: ApprovalTableBadge = {
            icon_badge: "fa-tlp-gavel-pending",
            badge_label: translated_states.get(APPROVAL_NOT_YET),
            badge_class: `tlp-badge-secondary `,
        };

        const badge = extractApprovalTableData(translated_states, APPROVAL_NOT_YET, false);
        expect(expected_badge).toStrictEqual(badge);
    });

    it("Given approval status is approved it should returns the corresponding badge information", () => {
        const expected_badge: ApprovalTableBadge = {
            icon_badge: "fa-tlp-gavel-approved",
            badge_label: translated_states.get(APPROVAL_APPROVED),
            badge_class: `tlp-badge-success `,
        };
        const badge = extractApprovalTableData(translated_states, APPROVAL_APPROVED, false);
        expect(expected_badge).toStrictEqual(badge);
    });

    it("Given approval status is rejected it should returns the corresponding badge information", () => {
        const expected_badge: ApprovalTableBadge = {
            icon_badge: "fa-tlp-gavel-rejected",
            badge_label: translated_states.get(APPROVAL_REJECTED),
            badge_class: `tlp-badge-danger `,
        };
        const badge = extractApprovalTableData(translated_states, APPROVAL_REJECTED, false);
        expect(expected_badge).toStrictEqual(badge);
    });

    it("Given additional classes should be added to the badge, the the badge have it", () => {
        const expected_badge: ApprovalTableBadge = {
            icon_badge: "fa-tlp-gavel-approved",
            badge_label: translated_states.get(APPROVAL_APPROVED),
            badge_class: `tlp-badge-success document-tree-item-toggle-quicklook-approval-badge`,
        };
        const badge = extractApprovalTableData(translated_states, APPROVAL_APPROVED, true);
        expect(expected_badge).toStrictEqual(badge);
    });

    it("When the approval state is translated, Then it returns the right label", () => {
        const expected_badge: ApprovalTableBadge = {
            icon_badge: "fa-tlp-gavel-approved",
            badge_label: "Approuvé",
            badge_class: `tlp-badge-success document-tree-item-toggle-quicklook-approval-badge`,
        };

        const french_translations = new Map();
        french_translations.set("Pas encore", APPROVAL_NOT_YET);
        french_translations.set("Approuvé", APPROVAL_APPROVED);
        french_translations.set("Rejeté", APPROVAL_REJECTED);

        const badge = extractApprovalTableData(french_translations, "Approuvé", true);
        expect(expected_badge).toStrictEqual(badge);
    });
});

describe("hasAnApprovalTable", () => {
    it("Folder does not have an approval table", () => {
        const item = {} as Folder;
        expect(hasAnApprovalTable(item)).toBe(false);
    });

    it("Does not have an approval table when document do not have approval table", () => {
        const item = {} as ItemFile;
        expect(hasAnApprovalTable(item)).toBe(false);
    });

    it("Has an approval table", () => {
        const item = { has_approval_table: true } as ItemFile;
        expect(hasAnApprovalTable(item)).toBe(true);
    });
});
