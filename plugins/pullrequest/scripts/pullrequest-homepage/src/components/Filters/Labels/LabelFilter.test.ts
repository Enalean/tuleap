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
import { LabelFilterBuilder, TYPE_FILTER_LABEL } from "./LabelFilter";
import { GettextStub } from "../../../../tests/stubs/GettextStub";
import { ProjectLabelStub } from "../../../../tests/stubs/ProjectLabelStub";

describe("LabelFilter", () => {
    it("Given a ProjectLabel, then it should return a LabelFilter", () => {
        const label = ProjectLabelStub.regulardWithIdAndLabel(1, "Emergency");
        const filter = LabelFilterBuilder(GettextStub).fromLabel(label);

        expect(filter.id).toBe(label.id);
        expect(filter.type).toBe(TYPE_FILTER_LABEL);
        expect(filter.label).toBe(`Label: ${label.label}`);
        expect(filter.value).toBe(label);
        expect(filter.is_unique).toBe(false);
    });
});
