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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-tests";
import FieldShortname from "./FieldShortname.vue";

describe("FieldShortname", () => {
    let state: State, mock_set_tracker_short_name: jest.Mock;

    beforeEach(() => {
        mock_set_tracker_short_name = jest.fn();
        state = {
            tracker_to_be_created: {
                name: "Kanban in the trees",
                shortname: "kanban_in_the_trees",
            },
        } as State;
    });

    function getWrapper(
        can_display_slugify_mode: boolean,
        is_shortname_valid = true,
        is_shortname_already_used = true,
    ): VueWrapper {
        return shallowMount(FieldShortname, {
            global: {
                ...getGlobalTestOptions({
                    state,
                    getters: {
                        can_display_slugify_mode: () => can_display_slugify_mode,
                        is_shortname_valid: () => is_shortname_valid,
                        is_shortname_already_used: () => is_shortname_already_used,
                    },
                    mutations: {
                        setTrackerShortName: mock_set_tracker_short_name,
                    },
                }),
            },
        });
    }

    it("The input is rendered", () => {
        const wrapper = getWrapper(false);
        const shortname_input = wrapper.find("[data-test=tracker-shortname-input]");

        expect(shortname_input.exists()).toBe(true);
    });

    it("is initialized with the tracker shortname from the store", () => {
        const wrapper = getWrapper(false);
        const input_element = wrapper.get<HTMLInputElement>(
            "[data-test=tracker-shortname-input]",
        ).element;

        expect(input_element.value).toBe(state.tracker_to_be_created.shortname);
    });

    it("sets the tracker shortname with the entered value on the keyup event", () => {
        const wrapper = getWrapper(false);
        const shortname_input = wrapper.get<HTMLInputElement>(
            "[data-test=tracker-shortname-input]",
        );

        shortname_input.trigger("keyup");

        expect(mock_set_tracker_short_name).toHaveBeenCalledWith(
            expect.anything(),
            shortname_input.element.value,
        );
    });

    it("If the slugify mode is active, then it displays the slugified mode", () => {
        const wrapper = getWrapper(true, true, false);

        expect(wrapper.find("field-shortname-slugified-stub").exists()).toBe(true);
    });

    it("Enters the error mode when the shortname does not respect the expected format", () => {
        const wrapper = getWrapper(false, false);

        expect(wrapper.find("[data-test=shortname-error]").exists()).toBe(true);
        expect(wrapper.classes("tlp-form-element-error")).toBe(true);
    });

    it("Enters the error mode when the chosen name already exist", () => {
        const wrapper = getWrapper(false, false, true);

        expect(wrapper.classes()).toContain("tlp-form-element-error");
        expect(wrapper.find("[data-test=shortname-taken-error]").exists()).toBe(true);
    });
});
