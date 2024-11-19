/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import ReorderArrows from "@/components/sidebar/toc/ReorderArrows.vue";

describe("ReorderArrows", () => {
    it("should display two move buttons for a section", () => {
        const wrapper = shallowMount(ReorderArrows, {
            props: {
                is_first: false,
                is_last: false,
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });

        expect(wrapper.find("[data-test=move-up]").exists()).toBe(true);
        expect(wrapper.find("[data-test=move-down]").exists()).toBe(true);
    });

    it("should display one move button for the first section", () => {
        const wrapper = shallowMount(ReorderArrows, {
            props: {
                is_first: true,
                is_last: false,
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });

        expect(wrapper.find("[data-test=move-up]").exists()).toBe(false);
        expect(wrapper.find("[data-test=move-down]").exists()).toBe(true);
    });

    it("should display one move button for the last section", () => {
        const wrapper = shallowMount(ReorderArrows, {
            props: {
                is_first: false,
                is_last: true,
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });

        expect(wrapper.find("[data-test=move-up]").exists()).toBe(true);
        expect(wrapper.find("[data-test=move-down]").exists()).toBe(false);
    });

    it("should NOT display any move button when there is only one section", () => {
        const wrapper = shallowMount(ReorderArrows, {
            props: {
                is_first: true,
                is_last: true,
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });

        expect(wrapper.find("[data-test=move-up]").exists()).toBe(false);
        expect(wrapper.find("[data-test=move-down]").exists()).toBe(false);
    });
});
