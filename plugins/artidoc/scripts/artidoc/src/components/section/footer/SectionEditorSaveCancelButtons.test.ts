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
import SectionEditorSaveCancelButtons from "./SectionEditorSaveCancelButtons.vue";
import type { SectionState } from "@/sections/states/SectionStateBuilder";
import { SectionStateStub } from "@/sections/stubs/SectionStateStub";
import { SectionEditorCloserStub } from "@/sections/stubs/SectionEditorCloserStub";
import { SaveSectionStub } from "@/sections/stubs/SaveSectionStub";
import type { SaveSection } from "@/sections/save/SectionSaver";
import type { CloseSectionEditor } from "@/sections/editors/SectionEditorCloser";

describe("SectionEditorSaveCancelButtons", () => {
    function getWrapper(
        section_state: SectionState,
        save_section: SaveSection,
        close_section_editor: CloseSectionEditor,
    ): VueWrapper<ComponentPublicInstance> {
        return shallowMount(SectionEditorSaveCancelButtons, {
            propsData: {
                save_section,
                section_state,
                close_section_editor,
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });
    }

    describe("when the edit mode is off", () => {
        it("should hide buttons", () => {
            expect(
                getWrapper(
                    SectionStateStub.withDefaults(),
                    SaveSectionStub.withNoExpectedCall(),
                    SectionEditorCloserStub.withNoExpectedCall(),
                )
                    .find("button")
                    .exists(),
            ).toBe(false);
        });
    });

    describe("when the edit mode is on", () => {
        it("should display buttons", () => {
            expect(
                getWrapper(
                    SectionStateStub.inEditMode(),
                    SaveSectionStub.withNoExpectedCall(),
                    SectionEditorCloserStub.withNoExpectedCall(),
                )
                    .find("button")
                    .exists(),
            ).toBe(true);
        });
    });

    describe("when save is not allowed", () => {
        it("should disable save button", () => {
            const wrapper = getWrapper(
                SectionStateStub.withDisallowedSave(),
                SaveSectionStub.withNoExpectedCall(),
                SectionEditorCloserStub.withNoExpectedCall(),
            );
            const save_button = wrapper
                .findAll("button")
                .find((button) => button.text().includes("Save"));
            expect(save_button?.exists()).toBe(true);
            expect(save_button?.element.disabled).toBe(true);
        });
    });

    describe("when save is allowed", () => {
        it("should enable save button", () => {
            const wrapper = getWrapper(
                SectionStateStub.inEditMode(),
                SaveSectionStub.withNoExpectedCall(),
                SectionEditorCloserStub.withNoExpectedCall(),
            );
            const save_button = wrapper
                .findAll("button")
                .find((button) => button.text().includes("Save"));
            expect(save_button?.exists()).toBe(true);
            expect(save_button?.element.disabled).toBe(false);
        });
    });

    it("When the user clicks on the save button, then it should save the section", () => {
        const section_saver = SaveSectionStub.withExpectNormalSave();
        const wrapper = getWrapper(
            SectionStateStub.inEditMode(),
            section_saver,
            SectionEditorCloserStub.withNoExpectedCall(),
        );

        wrapper.find("[data-test=save-button]").trigger("click");

        expect(section_saver.hasBeenNormallySaved()).toBe(true);
    });

    it("When the user clicks on the cancel button, then it should close the section editor", () => {
        const editor_closer = SectionEditorCloserStub.withExpectedCall();
        const wrapper = getWrapper(
            SectionStateStub.inEditMode(),
            SaveSectionStub.withNoExpectedCall(),
            editor_closer,
        );

        wrapper.find("[data-test=cancel-button]").trigger("click");

        expect(editor_closer.hasEditorBeenCanceledAndClosed()).toBe(true);
    });
});
