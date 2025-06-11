/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
import type { VueWrapper } from "@vue/test-utils";
import { Option } from "@tuleap/option";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import type { Cell } from "../../../domain/ArtifactsTable";
import {
    PRETTY_TITLE_CELL,
    TEXT_CELL,
    TRACKER_CELL,
    USER_CELL,
} from "../../../domain/ArtifactsTable";
import EmptySelectableCell from "./EmptySelectableCell.vue";

describe("EmptySelectableCell", () => {
    function getWrapper(cell: Cell): VueWrapper {
        return shallowMount(EmptySelectableCell, {
            global: { ...getGlobalTestOptions() },
            props: {
                cell,
                level: 0,
            },
        });
    }

    it("should display a pretty title empty cell for a pretty title column", () => {
        const pretty_title_cell: Cell = {
            type: PRETTY_TITLE_CELL,
            tracker_name: "lifesome",
            color: "inca-silver",
            artifact_id: 512,
            title: "earthmaking",
        };

        const wrapper = getWrapper(pretty_title_cell);

        expect(wrapper.find("[data-test=pretty-title-empty_cell").exists()).toBe(true);
        expect(wrapper.find("[data-test=empty_cell").exists()).toBe(false);
    });

    const user_cell: Cell = {
        type: USER_CELL,
        display_name: "Xiaodong Kang (xkang)",
        user_uri: Option.fromValue("/users/xkang"),
        avatar_uri: "https://example.com/themes/common/images/avatar_default.png",
    };
    const tracker_cell: Cell = { type: TRACKER_CELL, name: "ancientism", color: "peggy-pink" };
    const text_cell: Cell = { type: TEXT_CELL, value: "nassellarian amphistomoid" };

    it.each([[user_cell], [tracker_cell], [text_cell]])(
        "should display an empty cell for any other column type",
        (cell: Cell) => {
            const wrapper = getWrapper(cell);

            expect(wrapper.find("[data-test=pretty-title-empty_cell").exists()).toBe(false);
            expect(wrapper.find("[data-test=empty_cell").exists()).toBe(true);
        },
    );
});
