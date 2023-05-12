/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
import type { ProjectLabel } from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    getAssignableLabel,
    getAssignedLabelTemplate,
    getSelectedLabels,
} from "./AssignableLabelTemplate";

const label: ProjectLabel = {
    id: 102,
    label: "Bug fix",
    color: "clockwork-orange",
    is_outline: true,
};

describe("getAssignableLabelsTemplate", () => {
    describe("getAssignableLabel", () => {
        it("should return null if the provided value is not a label", () => {
            expect(getAssignableLabel({ foo: "bar" })).toBeNull();
        });

        it("should return the value if it is a label", () => {
            expect(getAssignableLabel(label)).toStrictEqual(label);
        });
    });

    describe("getSelectedLabels", () => {
        it("should return an empty array when the provided parameter is not an array", () => {
            expect(getSelectedLabels("abcd")).toStrictEqual([]);
        });

        it("should return an array of labels", () => {
            expect(getSelectedLabels([label])).toStrictEqual([label]);
        });
    });

    describe("getAssignedLabelTemplate", () => {
        it("should return a <tuleap-lazybox-selection-badge/> with the right color, aspect and content", () => {
            const badge = getAssignedLabelTemplate({
                value: { ...label },
                is_disabled: false,
            });

            expect(badge.getAttribute("color")).toBe(label.color);
            expect(badge.hasAttribute("outline")).toBe(true);
            expect(badge.textContent?.trim()).toBe(label.label);
        });
    });
});
