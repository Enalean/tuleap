/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { State } from "../../../../store/type";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { createTrackerCreationLocalVue } from "../../../../helpers/local-vue-for-tests";
import FieldName from "./FieldName.vue";

describe("FieldName", () => {
    let state: State;

    async function getWrapper(
        can_display_slugify_mode: boolean,
        is_name_already_used = false,
    ): Promise<Wrapper<FieldName>> {
        return shallowMount(FieldName, {
            mocks: {
                $store: createStoreMock({
                    state,
                    getters: {
                        can_display_slugify_mode,
                        is_name_already_used,
                    },
                }),
            },
            localVue: await createTrackerCreationLocalVue(),
        });
    }

    beforeEach(() => {
        state = {
            tracker_to_be_created: {
                name: "Kanban in the trees",
                shortname: "kanban_in_the_trees",
            },
        } as State;
    });

    it("its value is initialized with the tracker name from the store", async () => {
        const wrapper = await getWrapper(true);
        const input_element: HTMLInputElement = wrapper.get("[data-test=tracker-name-input]")
            .element as HTMLInputElement;

        expect(input_element.value).toEqual(state.tracker_to_be_created.name);
    });

    it("has the class tracker-name-above-slugified-shortname when slugify mode is active", async () => {
        const wrapper = await getWrapper(true);

        expect(wrapper.classes()).toContain("tracker-name-above-slugified-shortname");
    });

    it("sets the tracker name with the entered value on the keyup event", async () => {
        const wrapper = await getWrapper(true);
        const name_input = wrapper.get("[data-test=tracker-name-input]");

        name_input.trigger("keyup");

        const input_element: HTMLInputElement = name_input.element as HTMLInputElement;

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
            "setTrackerName",
            input_element.value,
        );
    });

    it("Enters the error mode when the chosen name already exist", async () => {
        const wrapper = await getWrapper(true, true);

        expect(wrapper.classes()).toContain("tlp-form-element-error");
        expect(wrapper.find("[data-test=name-error]").exists()).toBe(true);
    });
});
