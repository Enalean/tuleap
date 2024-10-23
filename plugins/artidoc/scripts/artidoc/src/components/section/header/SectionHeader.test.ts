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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SectionHeader from "./SectionHeader.vue";
import { ref } from "vue";
import { createGettext } from "vue3-gettext";
import { EDITOR_CHOICE } from "@/helpers/editor-choice";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";

const current_title = "Current section title";

const expectAReadonlyTitle = (wrapper: VueWrapper): void => {
    const readonly_title = wrapper.find("h1");
    expect(readonly_title.exists()).toBe(true);
    expect(readonly_title.text()).toBe(current_title);

    expect(wrapper.find("textarea").exists()).toBe(false);
};

describe("SectionHeader", () => {
    let is_prose_mirror: boolean, can_user_edit_document: boolean;

    beforeEach(() => {
        is_prose_mirror = true;
        can_user_edit_document = true;
    });

    const getWrapper = (overriden_props = {}): VueWrapper =>
        shallowMount(SectionHeader, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [EDITOR_CHOICE.valueOf()]: { is_prose_mirror: ref(is_prose_mirror) },
                    [CAN_USER_EDIT_DOCUMENT.valueOf()]: can_user_edit_document,
                },
            },
            props: {
                title: current_title,
                is_edit_mode: false,
                input_current_title: (): void => {
                    // Do nothing
                },
                is_print_mode: false,
                ...overriden_props,
            },
        });

    describe("In legacy mode (ckeditor)", () => {
        beforeEach(() => {
            is_prose_mirror = false;
        });

        it("When the section is in edit mode, then it should display a textarea containing the current title", () => {
            const input_current_title = vi.fn();

            const wrapper = getWrapper({
                is_edit_mode: true,
                input_current_title,
            });

            const textarea = wrapper.find("textarea");
            expect(textarea.exists()).toBe(true);
            expect(textarea.element.value).toBe(current_title);

            textarea.element.value = "new title";
            textarea.trigger("input");

            expect(input_current_title).toHaveBeenCalledWith("new title");
        });

        it("When the section is not in edit mode, then it should display a readonly title", () => {
            const wrapper = getWrapper({
                is_edit_mode: false,
                is_print_mode: false,
            });
            expectAReadonlyTitle(wrapper);
        });

        it("When the section is in print mode, then it should display a readonly title", () => {
            const wrapper = getWrapper({
                is_edit_mode: true,
                is_print_mode: true,
            });
            expectAReadonlyTitle(wrapper);
        });

        it("When user cannot edit the document, then it should display a readonly title", () => {
            can_user_edit_document = false;

            const wrapper = getWrapper({
                is_edit_mode: false,
                is_print_mode: false,
            });
            expectAReadonlyTitle(wrapper);
        });
    });

    describe("In nextgen mode (prosemirror)", () => {
        beforeEach(() => {
            is_prose_mirror = true;
        });

        it("It should display a textarea with special classes and containing the current title", () => {
            const input_current_title = vi.fn();
            const wrapper = getWrapper({
                is_edit_mode: false,
                is_print_mode: false,
                input_current_title,
            });

            const textarea = wrapper.find("textarea");
            expect(textarea.exists()).toBe(true);
            expect(textarea.classes()).toContain("add-hover-effect");
            expect(textarea.classes()).toContain("disable-border");
            expect(textarea.element.value).toBe(current_title);

            textarea.element.value = "new title";
            textarea.trigger("input");

            expect(input_current_title).toHaveBeenCalledWith("new title");
        });

        it("When the current user cannot edit the document, then it should display a readonly title", () => {
            can_user_edit_document = false;

            const wrapper = getWrapper({
                is_edit_mode: false,
                is_print_mode: false,
            });
            expectAReadonlyTitle(wrapper);
        });

        it("When the section is in print mode, then it should display a readonly title", () => {
            const wrapper = getWrapper({
                is_edit_mode: false,
                is_print_mode: true,
            });
            expectAReadonlyTitle(wrapper);
        });
    });
});
