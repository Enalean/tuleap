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
import FieldShortname from "./FieldShortname.vue";

describe("FieldShortname", () => {
    let state: State;

    async function getWrapper(
        can_display_slugify_mode: boolean,
        is_shortname_valid = true,
        is_shortname_already_used = true,
    ): Promise<Wrapper<FieldShortname>> {
        return shallowMount(FieldShortname, {
            mocks: {
                $store: createStoreMock({
                    state,
                    getters: {
                        can_display_slugify_mode,
                        is_shortname_valid,
                        is_shortname_already_used,
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

    it("The input is rendered", async () => {
        const wrapper = await getWrapper(false);
        const shortname_input = wrapper.get("[data-test=tracker-shortname-input]");

        expect(shortname_input.exists()).toBe(true);
    });

    it("is initialized with the tracker shortname from the store", async () => {
        const wrapper = await getWrapper(false);
        const shortname_input = wrapper.get("[data-test=tracker-shortname-input]");
        const input_element: HTMLInputElement = shortname_input.element as HTMLInputElement;

        expect(input_element.value).toEqual(state.tracker_to_be_created.shortname);
    });

    it("sets the tracker shortname with the entered value on the keyup event", async () => {
        const wrapper = await getWrapper(false);
        const shortname_input = wrapper.get("[data-test=tracker-shortname-input]");

        shortname_input.trigger("keyup");

        const input_element: HTMLInputElement = shortname_input.element as HTMLInputElement;

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
            "setTrackerShortName",
            input_element.value,
        );
    });

    it("If the slugify mode is active, then it displays the slugified mode", async () => {
        const wrapper = await getWrapper(true, true, false);

        expect(wrapper.find("field-shortname-slugified-stub").exists()).toBe(true);
    });

    it("Enters the error mode when the shortname does not respect the expected format", async () => {
        const wrapper = await getWrapper(false, false);

        expect(wrapper.find("[data-test=shortname-error]").exists()).toBe(true);
        expect(wrapper.classes("tlp-form-element-error")).toBe(true);
    });

    it("Enters the error mode when the chosen name already exist", async () => {
        const wrapper = await getWrapper(false, false, true);

        expect(wrapper.classes()).toContain("tlp-form-element-error");
        expect(wrapper.find("[data-test=shortname-taken-error]").exists()).toBe(true);
    });
});
