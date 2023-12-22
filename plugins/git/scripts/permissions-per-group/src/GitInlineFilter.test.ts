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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

import { shallowMount } from "@vue/test-utils";
import GitInlineFilter from "./GitInlineFilter.vue";
import { createGettext } from "vue3-gettext";

describe("GitInlineFilter", () => {
    it("When user types on keyboard, Then event is emitted", () => {
        const wrapper = shallowMount(GitInlineFilter, {
            props: {
                modelValue: "lorem",
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });
        wrapper.find("[data-test=git-inline-filter-input]").trigger("keyup");
        expect(wrapper.emitted("update:modelValue")).toBeTruthy();
    });
});
