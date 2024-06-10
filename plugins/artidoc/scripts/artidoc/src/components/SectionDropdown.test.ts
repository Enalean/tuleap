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
import { describe, expect, it, vi } from "vitest";
import type { DOMWrapper, VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SectionDropdown from "./SectionDropdown.vue";
import type { ComponentPublicInstance } from "vue";
import { createGettext } from "vue3-gettext";
import { SectionEditorStub } from "@/helpers/stubs/SectionEditorStub";
import type { SectionEditor } from "@/composables/useSectionEditor";
import ArtidocSectionFactory from "@/helpers/artidoc-section.factory";

vi.mock("@tuleap/tlp-dropdown");

describe("SectionDropdown", () => {
    function getWrapper(editor: SectionEditor): VueWrapper<ComponentPublicInstance> {
        return shallowMount(SectionDropdown, {
            propsData: {
                editor,
                section: ArtidocSectionFactory.create(),
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });
    }

    function getEditCta(editor: SectionEditor): DOMWrapper<Element> {
        return getWrapper(editor).find("[data-test=edit]");
    }

    describe("when the edit mode is off", () => {
        it("should display edit cta", () => {
            expect(getEditCta(SectionEditorStub.withEditableSection()).exists()).toBe(true);
        });
    });

    describe("when the edit mode is on", () => {
        it("should disable edit cta", () => {
            const cta = getEditCta(SectionEditorStub.inEditMode());
            expect(cta.exists()).toBe(true);
            expect(cta.classes()).toContain("tlp-dropdown-menu-item-disabled");
        });
    });

    describe("when the user is not allowed to edit the section", () => {
        it("should hide edit cta", () => {
            expect(getEditCta(SectionEditorStub.withoutEditableSection()).exists()).toBe(false);
        });
    });
});
