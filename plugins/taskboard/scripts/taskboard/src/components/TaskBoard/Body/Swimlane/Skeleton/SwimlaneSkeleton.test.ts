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
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import SwimlaneSkeleton from "./SwimlaneSkeleton.vue";
import ColumnsSkeleton from "./ColumnsSkeleton.vue";
import type { RootState } from "../../../../../store/type";

describe("SwimlaneSkeleton", () => {
    it("displays a fixed amount of skeletons in each column", () => {
        const wrapper = shallowMount(SwimlaneSkeleton, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        column: {
                            columns: [
                                { label: "Eeny" },
                                { label: "Meeny" },
                                { label: "Miny" },
                                { label: "Moe" },
                                { label: "Catch a tiger" },
                                { label: "By the toe" },
                                { label: "If he hollers" },
                                { label: "Let him go" },
                            ],
                        },
                    } as RootState,
                }),
            },
        });

        expect(
            wrapper
                .get(".taskboard-cell")
                .find(".taskboard-card-parent.taskboard-card-skeleton")
                .exists(),
        ).toBe(true);
        const skeletons = wrapper.findAllComponents(ColumnsSkeleton);
        expect(skeletons).toHaveLength(8);
        for (let i = 0; i < 8; i++) {
            expect(skeletons.at(i).props("column_index")).toBe(i);
        }
    });
});
