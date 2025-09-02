/*
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

import { shallowMount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import QueryResultsRow from "./QueryResultsRow.vue";
import type { ProjectReference } from "@tuleap/core-rest-api-types";

describe("QueryResultsRow", () => {
    it.each([
        [[0], "0:00"],
        [[1], "0:01"],
        [[61], "1:01"],
        [[4200, 86], "71:26"],
    ])(
        "when we have the following minutes %s then we should sum them and display %s",
        (minutes: number[], expected: string) => {
            const user = {
                id: 1858,
                user_url: "/users/alice.hernandez",
                display_name: "Alice Hernandez (alice.hernandez)",
                avatar_url: "/avatar-ea78.png",
            };
            const project: ProjectReference = {
                id: 1,
                label: "acme-project",
                icon: "",
                uri: "/project/1",
            };

            const wrapper = shallowMount(QueryResultsRow, {
                props: {
                    user_times: {
                        user,
                        times: minutes.map((minutes) => ({
                            project,
                            minutes,
                        })),
                    },
                },
            });

            expect(wrapper.find("[data-test=times]").text()).toBe(expected);
        },
    );
});
