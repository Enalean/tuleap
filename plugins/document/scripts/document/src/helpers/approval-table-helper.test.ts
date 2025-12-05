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
import {
    extractApprovalTableData,
    hasAnApprovalTable,
    isItemVersionable,
    isTableLinkedToLastItemVersion,
    rearrangeReviewersTable,
} from "./approval-table-helper";
import {
    APPROVAL_APPROVED,
    APPROVAL_NOT_YET,
    APPROVAL_REJECTED,
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../constants";
import type { ApprovableDocument, Folder, Item, ItemFile } from "../type";
import { ItemBuilder } from "../../tests/builders/ItemBuilder";
import { ApprovalTableBuilder } from "../../tests/builders/ApprovalTableBuilder";
import { ApprovalTableReviewerBuilder } from "../../tests/builders/ApprovalTableReviewerBuilder";

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

describe("rearrangeReviewersTable", () => {
    it("Can move reviewer down", () => {
        const reviewer1 = new ApprovalTableReviewerBuilder(102).withRank(0).build();
        const reviewer2 = new ApprovalTableReviewerBuilder(103).withRank(1).build();
        const reviewer3 = new ApprovalTableReviewerBuilder(104).withRank(2).build();
        const reviewer4 = new ApprovalTableReviewerBuilder(105).withRank(3).build();

        expect(
            rearrangeReviewersTable([reviewer1, reviewer2, reviewer3, reviewer4], reviewer2, 2),
        ).toStrictEqual([
            reviewer1,
            { ...reviewer3, rank: 1 },
            { ...reviewer2, rank: 2 },
            reviewer4,
        ]);
    });

    it("Can move reviewer up", () => {
        const reviewer1 = new ApprovalTableReviewerBuilder(102).withRank(0).build();
        const reviewer2 = new ApprovalTableReviewerBuilder(103).withRank(1).build();
        const reviewer3 = new ApprovalTableReviewerBuilder(104).withRank(2).build();
        const reviewer4 = new ApprovalTableReviewerBuilder(105).withRank(3).build();

        expect(
            rearrangeReviewersTable([reviewer1, reviewer2, reviewer3, reviewer4], reviewer2, 0),
        ).toStrictEqual([
            { ...reviewer2, rank: 0 },
            { ...reviewer1, rank: 1 },
            reviewer3,
            reviewer4,
        ]);
    });

    it("Can move reviewer at bottom", () => {
        const reviewer1 = new ApprovalTableReviewerBuilder(102).withRank(0).build();
        const reviewer2 = new ApprovalTableReviewerBuilder(103).withRank(1).build();
        const reviewer3 = new ApprovalTableReviewerBuilder(104).withRank(2).build();
        const reviewer4 = new ApprovalTableReviewerBuilder(105).withRank(3).build();

        expect(
            rearrangeReviewersTable([reviewer1, reviewer2, reviewer3, reviewer4], reviewer2, 3),
        ).toStrictEqual([
            reviewer1,
            { ...reviewer3, rank: 1 },
            { ...reviewer4, rank: 2 },
            { ...reviewer2, rank: 3 },
        ]);
    });

    it("Can move reviewer at top", () => {
        const reviewer1 = new ApprovalTableReviewerBuilder(102).withRank(0).build();
        const reviewer2 = new ApprovalTableReviewerBuilder(103).withRank(1).build();
        const reviewer3 = new ApprovalTableReviewerBuilder(104).withRank(2).build();
        const reviewer4 = new ApprovalTableReviewerBuilder(105).withRank(3).build();

        expect(
            rearrangeReviewersTable([reviewer1, reviewer2, reviewer3, reviewer4], reviewer4, 0),
        ).toStrictEqual([
            { ...reviewer4, rank: 0 },
            { ...reviewer1, rank: 1 },
            { ...reviewer2, rank: 2 },
            { ...reviewer3, rank: 3 },
        ]);
    });

    it("Do not move the reviewer", () => {
        const reviewer1 = new ApprovalTableReviewerBuilder(102).withRank(0).build();
        const reviewer2 = new ApprovalTableReviewerBuilder(103).withRank(1).build();
        const reviewer3 = new ApprovalTableReviewerBuilder(104).withRank(2).build();
        const reviewer4 = new ApprovalTableReviewerBuilder(105).withRank(3).build();

        expect(
            rearrangeReviewersTable([reviewer1, reviewer2, reviewer3, reviewer4], reviewer3, 2),
        ).toStrictEqual([reviewer1, reviewer2, reviewer3, reviewer4]);
    });

    it("Cannot move at a negative rank", () => {
        const reviewer1 = new ApprovalTableReviewerBuilder(102).withRank(0).build();
        const reviewer2 = new ApprovalTableReviewerBuilder(103).withRank(1).build();
        const reviewer3 = new ApprovalTableReviewerBuilder(104).withRank(2).build();
        const reviewer4 = new ApprovalTableReviewerBuilder(105).withRank(3).build();

        expect(
            rearrangeReviewersTable([reviewer1, reviewer2, reviewer3, reviewer4], reviewer1, -3),
        ).toStrictEqual([reviewer1, reviewer2, reviewer3, reviewer4]);
    });

    it("Cannot move far after the end", () => {
        const reviewer1 = new ApprovalTableReviewerBuilder(102).withRank(0).build();
        const reviewer2 = new ApprovalTableReviewerBuilder(103).withRank(1).build();
        const reviewer3 = new ApprovalTableReviewerBuilder(104).withRank(2).build();
        const reviewer4 = new ApprovalTableReviewerBuilder(105).withRank(3).build();

        expect(
            rearrangeReviewersTable(
                [reviewer1, reviewer2, reviewer3, reviewer4],
                reviewer4,
                Number.MAX_VALUE,
            ),
        ).toStrictEqual([reviewer1, reviewer2, reviewer3, reviewer4]);
    });
});

describe("isItemVersionable", () => {
    it.each([
        [TYPE_FOLDER, false],
        [TYPE_FILE, true],
        [TYPE_LINK, true],
        [TYPE_EMBEDDED, true],
        [TYPE_WIKI, true],
        [TYPE_EMPTY, false],
    ])(`Item of type %s is versionable: %s`, (type: string, is_versionable: boolean) => {
        const item = new ItemBuilder(123).withType(type).build();

        expect(isItemVersionable(item)).toBe(is_versionable);
    });
});

describe("isTableLinkedToLastItemVersion", () => {
    it("Folders are always on last version", () => {
        const item = new ItemBuilder(123).withType(TYPE_FOLDER).buildApprovableDocument();
        const table = new ApprovalTableBuilder(35).build();

        expect(isTableLinkedToLastItemVersion(item, table)).toBe(true);
    });

    it.each([
        [
            {
                ...new ItemBuilder(123).withType(TYPE_FILE).buildApprovableDocument(),
                file_properties: { version_number: 15 },
            },
        ],
        [
            {
                ...new ItemBuilder(123).withType(TYPE_LINK).buildApprovableDocument(),
                link_properties: { version_number: 15 },
            },
        ],
        [
            {
                ...new ItemBuilder(123).withType(TYPE_EMBEDDED).buildApprovableDocument(),
                embedded_file_properties: { version_number: 15 },
            },
        ],
        [
            {
                ...new ItemBuilder(123).withType(TYPE_WIKI).buildApprovableDocument(),
                wiki_properties: { version_number: 15 },
            },
        ],
    ])("Table is linked to last version", (item: Item & ApprovableDocument) => {
        expect(
            isTableLinkedToLastItemVersion(
                item,
                new ApprovalTableBuilder(35).withVersionNumber(15).build(),
            ),
        ).toBe(true);
        expect(
            isTableLinkedToLastItemVersion(
                item,
                new ApprovalTableBuilder(35).withVersionNumber(45).build(),
            ),
        ).toBe(false);
    });
});
