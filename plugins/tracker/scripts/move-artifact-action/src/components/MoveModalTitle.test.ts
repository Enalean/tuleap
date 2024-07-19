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

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { ColorName } from "@tuleap/core-constants";
import { ARTIFACT_ID, TRACKER_COLOR, TRACKER_NAME } from "../injection-symbols";
import MoveModalTitle from "./MoveModalTitle.vue";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";

describe("MoveModalTitle", () => {
    it("should display the artifact id with its tracker name and color", () => {
        const tracker_color: ColorName = "red-wine";
        const tracker_name = "Tasks";
        const artifact_id = 126;

        const wrapper = shallowMount(MoveModalTitle, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [TRACKER_NAME.valueOf()]: tracker_name,
                    [TRACKER_COLOR.valueOf()]: tracker_color,
                    [ARTIFACT_ID.valueOf()]: artifact_id,
                },
            },
        });

        const artifact_xref = wrapper.find("[data-test=artifact-xref]");
        expect(artifact_xref.classes()).toStrictEqual([tracker_color, "xref-in-title"]);
        expect(artifact_xref.element.textContent?.trim()).toBe("Tasks #126");
    });
});
