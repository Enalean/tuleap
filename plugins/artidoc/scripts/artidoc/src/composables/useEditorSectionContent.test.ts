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
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { EditorSectionContent } from "@/composables/useEditorSectionContent";
import { useEditorSectionContent } from "@/composables/useEditorSectionContent";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import { ref } from "vue";

type ShowButtonsCallbacks = { showActionsButtons: Mock; hideActionsButtons: Mock };
const getCallbacks = (): ShowButtonsCallbacks => {
    return {
        showActionsButtons: vi.fn(),
        hideActionsButtons: vi.fn(),
    };
};
const section = ref(ArtifactSectionFactory.create());

describe("useEditorSectionContent", () => {
    let section_content: EditorSectionContent, callbacks: ShowButtonsCallbacks;

    beforeEach(() => {
        callbacks = getCallbacks();
        section_content = useEditorSectionContent(section, callbacks);
    });

    describe("On EditorSectionContent initialization", () => {
        it("should init the editable_title and the editable_description with the current section title and description", () => {
            expect(section_content.editable_title.value).toBe(section.value.title.value);
            expect(section_content.editable_description.value).toBe(
                section.value.description.value,
            );
        });
    });

    describe("get_readonly_description", () => {
        it("should return the read only description", () => {
            expect(section_content.getReadonlyDescription()).toBe(section.value.description.value);
        });
    });

    describe("inputSectionContent", () => {
        it("should update the editable title and the editable description", () => {
            section_content.inputSectionContent("new title", "new description");

            expect(section_content.editable_title.value).toBe("new title");
            expect(section_content.editable_description.value).toBe("new description");
        });
        describe("when the user types something in the artidoc-section-title", () => {
            it("should display actions buttons", () => {
                section_content.inputSectionContent("new title", section.value.description.value);

                expect(section_content.editable_title.value).toBe("new title");
                expect(section_content.editable_description.value).toBe(
                    section.value.description.value,
                );
                expect(callbacks.showActionsButtons).toHaveBeenCalledOnce();
            });
        });

        describe("when the user types something in the artidoc-section-description", () => {
            it("should display actions buttons", () => {
                section_content.inputSectionContent(section.value.title.value, "new description");

                expect(section_content.editable_description.value).toBe("new description");
                expect(section_content.editable_title.value).toBe(section.value.title.value);
                expect(callbacks.showActionsButtons).toHaveBeenCalledOnce();
            });
        });

        describe("when the user focuses the editor but no change has occurred", () => {
            it("should hide actions buttons", () => {
                section_content.inputSectionContent(
                    section.value.title.value,
                    section.value.description.value,
                );

                expect(callbacks.hideActionsButtons).toHaveBeenCalledOnce();
            });
        });
    });

    describe("reset_content", () => {
        it("should reset description and title to the original state", () => {
            section_content.editable_title.value = "new title";
            section_content.editable_description.value = "new description";
            expect(section_content.editable_title.value).toEqual("new title");
            expect(section_content.editable_description.value).toEqual("new description");

            section_content.resetContent();

            expect(section_content.editable_title.value).toEqual(section.value.display_title);
            expect(section_content.editable_description.value).toEqual(
                section.value.description.value,
            );
        });
    });
});
