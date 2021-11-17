/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import type { Wrapper } from "@vue/test-utils";

import { shallowMount } from "@vue/test-utils";
import IterationCard from "./IterationCard.vue";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import { createPlanIterationsLocalVue } from "../helpers/local-vue-for-test";
import { formatDateYearMonthDay } from "@tuleap/date-helper/src";
import type { Iteration } from "../type";

describe("IterationCard", () => {
    let iteration: Iteration;

    async function getWrapper(): Promise<Wrapper<IterationCard>> {
        return shallowMount(IterationCard, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        user_locale: "en-US",
                    },
                }),
            },
            localVue: await createPlanIterationsLocalVue(),
            propsData: {
                iteration,
            },
        });
    }

    beforeEach(() => {
        iteration = {
            id: 1279,
            title: "Iteration 1",
            status: "On going",
            start_date: "2021-10-01T00:00:00+02:00",
            end_date: "2021-10-15T00:00:00+02:00",
        };
    });

    it("displays the content of an iteration", async () => {
        const wrapper = await getWrapper();

        expect(
            wrapper.get("[data-test=iteration-header-label]").text().includes(iteration.title)
        ).toBe(true);
        expect(
            wrapper
                .get("[data-test=iteration-header-dates]")
                .text()
                .includes(formatDateYearMonthDay("en-US", iteration.start_date))
        ).toBe(true);
        expect(
            wrapper
                .get("[data-test=iteration-header-dates]")
                .text()
                .includes(formatDateYearMonthDay("en-US", iteration.end_date))
        ).toBe(true);
        expect(
            wrapper.get("[data-test=iteration-header-status]").text().includes(iteration.status)
        ).toBe(true);
    });
});
