/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import type { LazyboxItem } from "@tuleap/lazybox";
import { ProjectLabelStub } from "../../../../tests/stubs/ProjectLabelStub";
import { LabelsFilteringCallback } from "./LabelsFilteringCallback";

const emergency_label: LazyboxItem = {
    value: ProjectLabelStub.outlinedWithIdAndLabel(1, "Emergency"),
    is_disabled: false,
};
const easy_fix_label: LazyboxItem = {
    value: ProjectLabelStub.outlinedWithIdAndLabel(2, "Easy fix"),
    is_disabled: false,
};
const doc_label: LazyboxItem = {
    value: ProjectLabelStub.outlinedWithIdAndLabel(3, "Doc"),
    is_disabled: false,
};

describe("LabelsFilteringCallback", () => {
    it("Given an empty query and a collection of LazyboxItems, then it should return all the labels", () => {
        const all_labels = [emergency_label, easy_fix_label, doc_label];
        const filtered_labels = LabelsFilteringCallback("", all_labels);

        expect(filtered_labels).toHaveLength(3);
        expect(filtered_labels).toStrictEqual(all_labels);
    });

    it("Given a query and a collection of LazyboxItems, then it should only return labels corresponding to the query", () => {
        const filtered_labels = LabelsFilteringCallback("eas", [
            emergency_label,
            easy_fix_label,
            doc_label,
        ]);

        expect(filtered_labels).toHaveLength(1);
        expect(filtered_labels).toStrictEqual([easy_fix_label]);
    });
});
