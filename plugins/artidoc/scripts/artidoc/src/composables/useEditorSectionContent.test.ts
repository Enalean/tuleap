/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { Mock } from "vitest";
import { describe, expect, it, vi } from "vitest";
import { useEditorSectionContent } from "@/composables/useEditorSectionContent";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import { ref } from "vue";

const getCallbacks = (): { showActionsButtons: Mock; hideActionsButtons: Mock } => {
    return {
        showActionsButtons: vi.fn(),
        hideActionsButtons: vi.fn(),
    };
};
const section = ref(ArtifactSectionFactory.create());
describe("useEditorSectionContent", () => {
    describe("get_readonly_description", () => {
        it("should return the read only description", () => {
            const { getReadonlyDescription } = useEditorSectionContent(section, getCallbacks());
            expect(getReadonlyDescription()).toBe(section.value.description.value);
        });
    });
    describe("input_current_title", () => {
        it("should update editable title", () => {
            const { editable_title, inputCurrentTitle } = useEditorSectionContent(
                section,
                getCallbacks(),
            );
            expect(editable_title.value).toBe(section.value.display_title);
            inputCurrentTitle("new title");
            expect(editable_title.value).toBe("new title");
        });
        describe("when the user types something in the title area", () => {
            it("should display actions buttons", () => {
                const callbacks = getCallbacks();
                const { editable_title, inputCurrentTitle } = useEditorSectionContent(
                    section,
                    callbacks,
                );
                expect(editable_title.value).toBe(section.value.display_title);
                inputCurrentTitle("new title");
                expect(callbacks.showActionsButtons).toHaveBeenCalledOnce();
                expect(editable_title.value).toBe("new title");
            });
        });
        describe("when the user focuses on the area but no change has occurred", () => {
            it("should hide actions buttons", () => {
                const callbacks = getCallbacks();
                const { editable_title, inputCurrentTitle } = useEditorSectionContent(
                    section,
                    callbacks,
                );
                expect(editable_title.value).toBe(section.value.display_title);
                inputCurrentTitle(section.value.display_title);
                expect(callbacks.hideActionsButtons).toHaveBeenCalledOnce();
            });
        });
    });
    describe("input_current_description", () => {
        it("should update editable description", () => {
            const { editable_description, inputCurrentDescription } = useEditorSectionContent(
                section,
                getCallbacks(),
            );
            expect(editable_description.value).toBe(section.value.description.value);
            inputCurrentDescription("new description");
            expect(editable_description.value).toBe("new description");
        });
        describe("when the user types something in the title area", () => {
            it("should display actions buttons", () => {
                const callbacks = getCallbacks();
                const { editable_description, inputCurrentDescription } = useEditorSectionContent(
                    section,
                    callbacks,
                );
                expect(editable_description.value).toBe(section.value.description.value);
                inputCurrentDescription("new description");
                expect(editable_description.value).toBe("new description");
                expect(callbacks.showActionsButtons).toHaveBeenCalledOnce();
            });
        });
        describe("when the user focuses on the area but no change has occurred", () => {
            it("should hide actions buttons", () => {
                const callbacks = getCallbacks();
                const { editable_description, inputCurrentDescription } = useEditorSectionContent(
                    section,
                    callbacks,
                );
                expect(editable_description.value).toBe(section.value.description.value);
                inputCurrentDescription(section.value.description.value);
                expect(callbacks.hideActionsButtons).toHaveBeenCalledOnce();
            });
        });
    });
    describe("reset_content", () => {
        it("should reset description and title to the original state", () => {
            const { resetContent, editable_title, editable_description } = useEditorSectionContent(
                section,
                getCallbacks(),
            );

            editable_title.value = "new title";
            editable_description.value = "new description";
            expect(editable_title.value).toEqual("new title");
            expect(editable_description.value).toEqual("new description");

            resetContent();

            expect(editable_title.value).toEqual(section.value.display_title);
            expect(editable_description.value).toEqual(section.value.description.value);
        });
    });
});
