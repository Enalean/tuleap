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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { ComponentPublicInstance } from "vue";
import { createGettext } from "vue3-gettext";
import { SectionEditorStub } from "@/helpers/stubs/SectionEditorStub";
import type { SectionEditor } from "@/composables/useSectionEditor";
import SectionFooter from "./SectionFooter.vue";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";

describe("SectionFooter", () => {
    function getWrapper(editor: SectionEditor): VueWrapper<ComponentPublicInstance> {
        return shallowMount(SectionFooter, {
            propsData: {
                editor,
                section: ArtifactSectionFactory.create(),
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });
    }

    describe("when the section is not editable", () => {
        it("should hide the footer", () => {
            expect(
                getWrapper(SectionEditorStub.withoutEditableSection()).find("div").exists(),
            ).toBe(false);
        });
    });

    describe("when the section is editable", () => {
        describe("when the editor is disabled", () => {
            it("should add a background", () => {
                expect(
                    getWrapper(SectionEditorStub.withEditableSection())
                        .find(".section-footer-with-background")
                        .exists(),
                ).toBe(true);
            });
        });

        it("should display the footer", () => {
            expect(getWrapper(SectionEditorStub.withEditableSection()).find("div").exists()).toBe(
                true,
            );
        });
    });
});
