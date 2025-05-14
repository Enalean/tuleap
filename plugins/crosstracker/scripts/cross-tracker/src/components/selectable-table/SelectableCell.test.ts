/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { IntlFormatter } from "@tuleap/date-helper";
import { Option } from "@tuleap/option";
import SelectableCell from "./SelectableCell.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import type { Cell } from "../../domain/ArtifactsTable";
import {
    DATE_CELL,
    NUMERIC_CELL,
    PRETTY_TITLE_CELL,
    PROJECT_CELL,
    STATIC_LIST_CELL,
    TEXT_CELL,
    TRACKER_CELL,
    USER_CELL,
    USER_GROUP_LIST_CELL,
    USER_LIST_CELL,
} from "../../domain/ArtifactsTable";
import { DATE_FORMATTER, DATE_TIME_FORMATTER } from "../../injection-symbols";

describe(`SelectableCell`, () => {
    let artifact_uri: string, is_even: boolean, is_last_of_row: boolean;

    beforeEach(() => {
        artifact_uri = "/plugins/tracker/?aid=286";
        is_even = false;
        is_last_of_row = false;
    });

    const getWrapper = (cell: Cell): VueWrapper<InstanceType<typeof SelectableCell>> => {
        return shallowMount(SelectableCell, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [DATE_FORMATTER.valueOf()]: IntlFormatter("en_US", "Europe/Paris", "date"),
                    [DATE_TIME_FORMATTER.valueOf()]: IntlFormatter(
                        "en_US",
                        "Europe/Paris",
                        "date-with-time",
                    ),
                },
            },
            props: {
                cell,
                artifact_uri,
                even: is_even,
                last_of_row: is_last_of_row,
            },
        });
    };

    describe(`render()`, () => {
        function* generateCells(): Generator<[string, Cell]> {
            yield [
                PRETTY_TITLE_CELL,
                {
                    type: PRETTY_TITLE_CELL,
                    title: "earthmaking",
                    tracker_name: "lifesome",
                    artifact_id: 512,
                    color: "inca-silver",
                },
            ];
            yield [
                USER_CELL,
                {
                    type: USER_CELL,
                    display_name: "Xiaodong Kang (xkang)",
                    user_uri: Option.fromValue("/users/xkang"),
                    avatar_uri: "https://example.com/themes/common/images/avatar_default.png",
                },
            ];
            yield [TRACKER_CELL, { type: TRACKER_CELL, name: "ancientism", color: "peggy-pink" }];
            yield [TEXT_CELL, { type: TEXT_CELL, value: "nassellarian amphistomoid" }];
            yield [NUMERIC_CELL, { type: NUMERIC_CELL, value: Option.fromValue(31) }];
            yield [
                PROJECT_CELL,
                { type: PROJECT_CELL, name: "unpresentably shakiness", icon: "🧪" },
            ];
            yield [
                DATE_CELL,
                {
                    type: DATE_CELL,
                    value: Option.fromValue("1999-09-10T03:43:04+01:00"),
                    with_time: true,
                },
            ];
            yield [STATIC_LIST_CELL, { type: STATIC_LIST_CELL, value: [] }];
            yield [USER_LIST_CELL, { type: USER_LIST_CELL, value: [] }];
            yield [USER_GROUP_LIST_CELL, { type: USER_GROUP_LIST_CELL, value: [] }];
        }

        it.each([...generateCells()])(
            `sets the even class when the row is even for a %s cell`,
            (_cell_type, cell) => {
                is_even = true;
                const wrapper = getWrapper(cell);
                expect(wrapper.get("[data-test=cell]").classes()).toContain("even-row");
            },
        );

        it.each([...generateCells()])(
            `sets the odd class when the row is odd for a %s cell`,
            (_cell_type, cell) => {
                is_even = false;
                const wrapper = getWrapper(cell);
                expect(wrapper.get("[data-test=cell]").classes()).toContain("odd-row");
            },
        );

        it.each([...generateCells()])(
            `sets the last-of-row class when the cell is the last of its row for a %s cell`,
            (_cell_type, cell) => {
                is_last_of_row = true;
                const wrapper = getWrapper(cell);
                expect(wrapper.get("[data-test=cell]").classes()).toContain("last-of-row");
            },
        );
    });
});
