/**
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

import { describe, beforeEach, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import ReorderFieldsArrows from "@/components/configuration/ReorderFieldsArrows.vue";

const field: ConfigurationField = {
    field_id: 1234,
    label: "String Field",
    type: "string",
    display_type: "column",
};
describe("ReorderFieldsArrows", () => {
    let is_first: boolean, is_last: boolean;

    beforeEach(() => {
        is_first = false;
        is_last = false;
    });

    function getWrapper(): VueWrapper {
        return shallowMount(ReorderFieldsArrows, {
            props: {
                is_first,
                is_last,
                field,
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });
    }

    it("should display two move buttons for a field row", () => {
        const wrapper = getWrapper();

        const up_button = wrapper.find("[data-test=move-up]");
        const down_button = wrapper.find("[data-test=move-down]");

        expect(up_button.exists()).toBe(true);
        expect(down_button.exists()).toBe(true);
    });

    it("should hide the up button for the first field row", () => {
        is_first = true;
        const wrapper = getWrapper();

        const up_button = wrapper.find("[data-test=move-up]");
        const down_button = wrapper.find("[data-test=move-down]");

        expect(up_button.exists()).toBe(true);
        expect(up_button.classes()).toContain("hide-button");
        expect(down_button.exists()).toBe(true);
        expect(down_button.classes()).not.toContain("hide-button");
    });

    it("should hide the down button for the last field row", () => {
        is_last = true;
        const wrapper = getWrapper();

        const up_button = wrapper.find("[data-test=move-up]");
        const down_button = wrapper.find("[data-test=move-down]");

        expect(up_button.exists()).toBe(true);
        expect(up_button.classes()).not.toContain("hide-button");
        expect(down_button.exists()).toBe(true);
        expect(down_button.classes()).toContain("hide-button");
    });

    it("should hide the move buttons if there is only one field row", () => {
        is_first = true;
        is_last = true;
        const wrapper = getWrapper();

        const up_button = wrapper.find("[data-test=move-up]");
        const down_button = wrapper.find("[data-test=move-down]");

        expect(up_button.exists()).toBe(true);
        expect(up_button.classes()).toContain("hide-button");
        expect(down_button.exists()).toBe(true);
        expect(down_button.classes()).toContain("hide-button");
    });

    describe.each([["move-up"], ["move-down"]])("When the %s button is clicked", (button_name) => {
        it(`should emit a ${button_name} event`, () => {
            const wrapper = getWrapper();

            wrapper.find(`[data-test=${button_name}]`).trigger("click");

            const event = wrapper.emitted(`${button_name}`);
            if (!event) {
                throw new Error(`Expected a ${button_name} event`);
            }

            expect(event[0][0]).toStrictEqual(field);
        });
    });
});
