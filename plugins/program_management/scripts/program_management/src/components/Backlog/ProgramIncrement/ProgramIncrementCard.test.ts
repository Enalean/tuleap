/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import ProgramIncrementCard from "./ProgramIncrementCard.vue";
import { createProgramManagementLocalVue } from "../../../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";

import type { Wrapper } from "@vue/test-utils";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";

describe("ProgramIncrementCard", () => {
    async function getWrapper(increment: ProgramIncrement): Promise<Wrapper<ProgramIncrementCard>> {
        return shallowMount(ProgramIncrementCard, {
            localVue: await createProgramManagementLocalVue(),
            propsData: {
                increment,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            short_name: "guinea-pig",
                            tracker_iteration_label: "stuff",
                        },
                    },
                }),
            },
        });
    }

    it("Display a card with closed state", async () => {
        const wrapper = await getWrapper({
            id: 1,
            title: "PI 1",
            status: '"To be Planned',
            start_date: null,
            end_date: null,
            user_can_update: true,
        } as ProgramIncrement);

        expect(
            wrapper.get("[data-test=program-increment-toggle-icon]").classes("fa-caret-right")
        ).toBe(true);
        expect(
            wrapper.get("[data-test=program-increment-toggle-icon]").classes("fa-caret-down")
        ).toBe(false);
        expect(wrapper.find("[data-test=program-increment-info]").exists()).toBe(false);
        expect(wrapper.find("[data-test=program-increment-content]").exists()).toBe(false);
    });

    it("Don't display update button if user doesn't have the permission", async () => {
        const wrapper = await getWrapper({
            id: 1,
            title: "PI 1",
            status: '"To be Planned',
            start_date: null,
            end_date: null,
            user_can_update: false,
        } as ProgramIncrement);

        wrapper.get("[data-test=program-increment-toggle]").trigger("click");

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=program-increment-info]").exists()).toBe(false);
        expect(wrapper.find("[data-test=program-increment-content]").exists()).toBe(true);
    });

    it("Display a card and its content", async () => {
        const wrapper = await getWrapper({
            id: 1,
            title: "PI 1",
            status: '"To be Planned',
            start_date: null,
            end_date: null,
            user_can_update: true,
        } as ProgramIncrement);

        wrapper.get("[data-test=program-increment-toggle]").trigger("click");

        await wrapper.vm.$nextTick();

        expect(
            wrapper.get("[data-test=program-increment-toggle-icon]").classes("fa-caret-right")
        ).toBe(false);
        expect(
            wrapper.get("[data-test=program-increment-toggle-icon]").classes("fa-caret-down")
        ).toBe(true);
        expect(
            wrapper
                .get("[data-test=program-increment-info]")
                .classes("program-increment-info-hidden")
        ).toBe(false);
        expect(
            wrapper
                .get("[data-test=program-increment-content]")
                .classes("program-increment-content-hidden")
        ).toBe(false);
        expect(
            wrapper.get("[data-test=program-increment-info-edit-link]").attributes().href
        ).toEqual("/plugins/tracker/?aid=1&program_increment=update");
        expect(
            wrapper.get("[data-test=program-increment-plan-iterations-link]").attributes().href
        ).toEqual("/program_management/guinea-pig/increments/1/plan");
        expect(wrapper.get("[data-test=program-increment-plan-iterations-link]").text()).toEqual(
            "Plan stuff"
        );
    });
});
