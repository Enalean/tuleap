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
import { beforeAll, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SectionEditorCta from "./SectionEditorCta.vue";
import type { ComponentPublicInstance } from "vue";
import { createGettext } from "vue3-gettext";
const default_props = {
    is_edit_mode: false,
    editor_actions: {
        setEditMode: vi.fn(),
        saveEditor: vi.fn(),
        cancelEditor: vi.fn(),
    },
    is_section_editable: true,
};
const defaultGlobal = {
    plugins: [createGettext({ silent: true })],
};
describe("SectionEditorCta", () => {
    describe("when the edit mode is off", () => {
        let wrapper: VueWrapper<ComponentPublicInstance>;
        beforeAll(() => {
            wrapper = shallowMount(SectionEditorCta, {
                propsData: default_props,
                global: defaultGlobal,
            });
        });
        it("should display edit button", () => {
            const buttons = wrapper.findAll("button");
            const editButton = buttons.filter((button) => button.text() === "Edit");
            const cancelButton = buttons.filter((button) => button.text() === "Cancel");
            const saveButton = buttons.filter((button) => button.text() === "Save");
            expect(editButton).toHaveLength(1);
            expect(cancelButton).toHaveLength(0);
            expect(saveButton).toHaveLength(0);
        });
    });
    describe("when the edit mode is on", () => {
        let wrapper: VueWrapper<ComponentPublicInstance>;
        beforeAll(() => {
            wrapper = shallowMount(SectionEditorCta, {
                propsData: { ...default_props, is_edit_mode: true },
                global: defaultGlobal,
            });
        });
        it("should display save et cancel buttons", () => {
            const buttons = wrapper.findAll("button");
            const editButton = buttons.filter((button) => button.text() === "Edit");
            const cancelButton = buttons.filter((button) => button.text() === "Cancel");
            const saveButton = buttons.filter((button) => button.text() === "Save");
            expect(editButton).toHaveLength(0);
            expect(cancelButton).toHaveLength(1);
            expect(saveButton).toHaveLength(1);
        });
    });
    describe("when the user is not allowed to edit the section", () => {
        let wrapper: VueWrapper<ComponentPublicInstance>;
        beforeAll(() => {
            wrapper = shallowMount(SectionEditorCta, {
                propsData: { ...default_props, is_section_editable: false },
                global: defaultGlobal,
            });
        });
        it("should not display edit mode buttons", () => {
            const buttons = wrapper.findAll("button");
            const editButton = buttons.filter((button) => button.text() === "Edit");
            const cancelButton = buttons.filter((button) => button.text() === "Cancel");
            const saveButton = buttons.filter((button) => button.text() === "Save");
            expect(editButton).toHaveLength(0);
            expect(cancelButton).toHaveLength(0);
            expect(saveButton).toHaveLength(0);
        });
    });
});
