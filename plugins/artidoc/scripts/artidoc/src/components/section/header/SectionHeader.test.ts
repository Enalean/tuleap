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

import type { Mock } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SectionHeader from "./SectionHeader.vue";
import type { ComponentPublicInstance } from "vue";
import { ref } from "vue";
import { createGettext } from "vue3-gettext";
import { EDITOR_CHOICE } from "@/helpers/editor-choice";

function getWrapper(options: {
    is_edit_mode: boolean;
    input_current_title: Mock;
    is_prose_mirror: boolean;
}): VueWrapper<ComponentPublicInstance> {
    const { is_edit_mode, input_current_title, is_prose_mirror } = options;
    return shallowMount(SectionHeader, {
        propsData: {
            title: "expected title",
            is_edit_mode,
            input_current_title,
        },
        global: {
            plugins: [createGettext({ silent: true })],
            provide: {
                [EDITOR_CHOICE.valueOf()]: { is_prose_mirror: ref(is_prose_mirror) },
            },
        },
    });
}

describe("SectionHeader", () => {
    describe("when the edit mode is enable", () => {
        it("should display title in edit mode", () => {
            const input_current_title = vi.fn();

            const wrapper = getWrapper({
                is_edit_mode: true,
                input_current_title,
                is_prose_mirror: false,
            });

            const textarea = wrapper.find("textarea");
            expect(textarea.exists()).toBe(true);
            expect(textarea.element.value).toBe("expected title");

            textarea.element.value = "new title";
            textarea.trigger("input");

            expect(input_current_title).toHaveBeenCalledWith("new title");
        });
    });
    describe("when the edit mode is disable", () => {
        it("should display the title", () => {
            const wrapper = getWrapper({
                is_edit_mode: false,
                input_current_title: vi.fn(),
                is_prose_mirror: false,
            });
            expect(wrapper.find("h1").text()).toContain("expected title");
        });
    });
    describe("when prose mirror is enable", () => {
        let wrapper: VueWrapper<ComponentPublicInstance>;
        let input_current_title: Mock;

        beforeEach(() => {
            input_current_title = vi.fn();

            wrapper = getWrapper({
                is_edit_mode: false,
                input_current_title,
                is_prose_mirror: true,
            });
        });
        it("should display title in edit mode", () => {
            expect(wrapper.find("textarea").exists()).toBe(true);
        });
        it("should add an hover effect", () => {
            const textarea = wrapper.find("textarea");
            expect(textarea.exists()).toBe(true);
            expect(textarea.classes()).toContain("add-hover-effect");
        });
        it("should disable border", () => {
            const textarea = wrapper.find("textarea");
            expect(textarea.exists()).toBe(true);
            expect(textarea.classes()).toContain("disable-border");
        });
    });
});
