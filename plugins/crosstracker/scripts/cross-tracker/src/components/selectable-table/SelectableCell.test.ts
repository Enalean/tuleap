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
import { buildVueDompurifyHTMLDirective } from "vue-dompurify-html";
import { IntlFormatter } from "@tuleap/date-helper";
import { Option } from "@tuleap/option";
import SelectableCell from "./SelectableCell.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import type { Cell } from "../../domain/ArtifactsTable";
import {
    USER_CELL,
    DATE_CELL,
    NUMERIC_CELL,
    PRETTY_TITLE_CELL,
    PROJECT_CELL,
    TEXT_CELL,
    TRACKER_CELL,
} from "../../domain/ArtifactsTable";
import { DATE_FORMATTER, DATE_TIME_FORMATTER } from "../../injection-symbols";

describe(`SelectableCell`, () => {
    let artifact_uri: string, is_even: boolean;

    beforeEach(() => {
        artifact_uri = "/plugins/tracker/?aid=286";
        is_even = false;
    });

    const getWrapper = (cell: Cell): VueWrapper<InstanceType<typeof SelectableCell>> => {
        return shallowMount(SelectableCell, {
            global: {
                ...getGlobalTestOptions(),
                directives: {
                    "dompurify-html": buildVueDompurifyHTMLDirective(),
                },
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
            },
        });
    };

    describe(`render()`, () => {
        it(`when the cell is a pretty title, it renders a link to artifact URI`, () => {
            artifact_uri = "/plugins/tracker/?aid=76";
            const wrapper = getWrapper({
                type: PRETTY_TITLE_CELL,
                title: "uncensorable litigant",
                tracker_name: "story",
                artifact_id: 76,
                color: "coral-pink",
            });

            expect(wrapper.get("a").attributes("href")).toBe(artifact_uri);
        });

        describe(`when the cell is a user`, () => {
            it(`renders a link to the user URI and an img with the avatar URI`, () => {
                const user_uri = "/users/mcastro";
                const avatar_uri = "https://example.com/users/mcastro/avatar.png";
                const wrapper = getWrapper({
                    type: USER_CELL,
                    display_name: "Mario Castro (mcastro)",
                    user_uri: Option.fromValue(user_uri),
                    avatar_uri,
                });

                expect(wrapper.get("a").attributes("href")).toBe(user_uri);
                expect(wrapper.get("img").attributes("src")).toBe(avatar_uri);
            });

            it(`does not render a link when the user is anonymous`, () => {
                const wrapper = getWrapper({
                    type: USER_CELL,
                    display_name: "Anonymous user",
                    user_uri: Option.nothing(),
                    avatar_uri: "https://example.com/themes/common/images/avatar_default.png",
                });

                expect(wrapper.find("a").exists()).toBe(false);
            });
        });

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
    });
});
