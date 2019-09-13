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

import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper-jest";
import SwimlaneSkeleton from "./SwimlaneSkeleton.vue";

describe("SwimlaneSkeleton", () => {
    it("displays a fixed amount of skeletons in each column", () => {
        const wrapper = shallowMount(SwimlaneSkeleton, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        columns: [
                            { label: "Eeny" },
                            { label: "Meeny" },
                            { label: "Miny" },
                            { label: "Moe" },
                            { label: "Catch a tiger" },
                            { label: "By the toe" },
                            { label: "If he hollers" },
                            { label: "Let him go" }
                        ]
                    }
                })
            }
        });

        const columns = wrapper.findAll(".taskboard-cell");
        expect(columns.length).toBe(9);
        expect(columns.at(0).contains(".taskboard-card-parent.taskboard-card-skeleton")).toBe(true);
        expect(columns.at(0).findAll(".taskboard-card-skeleton").length).toBe(1);
        expect(columns.at(1).findAll(".taskboard-card-skeleton").length).toBe(4);
        expect(columns.at(2).findAll(".taskboard-card-skeleton").length).toBe(1);
        expect(columns.at(3).findAll(".taskboard-card-skeleton").length).toBe(2);
        expect(columns.at(4).findAll(".taskboard-card-skeleton").length).toBe(3);
        expect(columns.at(5).findAll(".taskboard-card-skeleton").length).toBe(1);
        expect(columns.at(6).findAll(".taskboard-card-skeleton").length).toBe(4);
        expect(columns.at(7).findAll(".taskboard-card-skeleton").length).toBe(1);
    });
});
