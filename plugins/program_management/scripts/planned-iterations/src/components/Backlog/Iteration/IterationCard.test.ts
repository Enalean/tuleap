/*
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import { formatDateYearMonthDay } from "@tuleap/date-helper";
import IterationCard from "./IterationCard.vue";
import type { Iteration } from "../../../type";

describe("IterationCard", () => {
    let iteration: Iteration;

    function getWrapper(): VueWrapper<InstanceType<typeof IterationCard>> {
        return shallowMount(IterationCard, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            namespaced: true,
                            state: { user_locale: "en-US", program_increment: { id: 1280 } },
                        },
                    },
                }),
            },
            props: { iteration },
        });
    }

    beforeEach(() => {
        iteration = {
            id: 1279,
            title: "Iteration 1",
            status: "On going",
            start_date: "2021-10-01T00:00:00+02:00",
            end_date: "2021-10-15T00:00:00+02:00",
            user_can_update: true,
        };
    });

    it("Display the iteration with a closed state", () => {
        const wrapper = getWrapper();

        expect(
            wrapper.get("[data-test=planned-iteration-toggle-icon]").classes("fa-caret-right"),
        ).toBe(true);
        expect(
            wrapper.get("[data-test=planned-iteration-toggle-icon]").classes("fa-caret-down"),
        ).toBe(false);
        expect(wrapper.find("[data-test=planned-iteration-content]").exists()).toBe(false);
        expect(wrapper.find("[data-test=planned-iteration-info]").exists()).toBe(false);
    });

    it("Display the iteration with an open state", async () => {
        const wrapper = getWrapper();

        await wrapper.get("[data-test=iteration-card-header]").trigger("click");

        expect(
            wrapper.get("[data-test=planned-iteration-toggle-icon]").classes("fa-caret-right"),
        ).toBe(false);
        expect(
            wrapper.get("[data-test=planned-iteration-toggle-icon]").classes("fa-caret-down"),
        ).toBe(true);
        expect(wrapper.find("[data-test=planned-iteration-content]").exists()).toBe(true);
        expect(wrapper.find("[data-test=planned-iteration-info]").exists()).toBe(true);
    });

    it("displays the content of an iteration", () => {
        const wrapper = getWrapper();

        expect(wrapper.get("[data-test=iteration-header-label]").text()).toContain(iteration.title);
        expect(wrapper.get("[data-test=iteration-header-dates]").text()).toContain(
            formatDateYearMonthDay("en-US", iteration.start_date),
        );
        expect(wrapper.get("[data-test=iteration-header-dates]").text()).toContain(
            formatDateYearMonthDay("en-US", iteration.end_date),
        );
        expect(wrapper.get("[data-test=iteration-header-status]").text()).toContain(
            iteration.status,
        );
    });

    it("should not display the info header if the user cannot update the iteration", () => {
        iteration.user_can_update = false;

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=planned-iteration-info]").exists()).toBe(false);
    });
});
