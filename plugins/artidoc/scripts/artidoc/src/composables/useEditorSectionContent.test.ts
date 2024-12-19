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
import FreetextSectionFactory from "@/helpers/freetext-section.factory";

type ShowButtonsCallbacks = { showActionsButtons: Mock; hideActionsButtons: Mock };
const getCallbacks = (): ShowButtonsCallbacks => {
    return {
        showActionsButtons: vi.fn(),
        hideActionsButtons: vi.fn(),
    };
};
const artifact_section = ref(ArtifactSectionFactory.create());
const freetext_section = ref(FreetextSectionFactory.create());

describe("useEditorSectionContent", () => {
    let artifact_section_content: EditorSectionContent,
        freetext_section_content: EditorSectionContent,
        callbacks: ShowButtonsCallbacks;

    beforeEach(() => {
        callbacks = getCallbacks();
        artifact_section_content = useEditorSectionContent(artifact_section, callbacks);
        freetext_section_content = useEditorSectionContent(freetext_section, callbacks);
    });

    describe("On EditorSectionContent initialization", () => {
        it("should init the editable_title and the editable_description with the current artifact section title and description", () => {
            expect(artifact_section_content.editable_title.value).toBe(
                artifact_section.value.title.value,
            );
            expect(artifact_section_content.editable_description.value).toBe(
                artifact_section.value.description.value,
            );
        });

        it("should init the editable_title and the editable_description with the current freetext section title and description", () => {
            expect(freetext_section_content.editable_title.value).toBe(
                freetext_section.value.title,
            );
            expect(freetext_section_content.editable_description.value).toBe(
                freetext_section.value.description,
            );
        });
    });

    describe("get_readonly_description", () => {
        it("should return the read only description", () => {
            expect(artifact_section_content.getReadonlyDescription()).toBe(
                artifact_section.value.description.value,
            );
            expect(freetext_section_content.getReadonlyDescription()).toBe(
                freetext_section.value.description,
            );
        });
    });

    describe("inputSectionContent", () => {
        it("should update the editable title and the editable description of the artifact section", () => {
            artifact_section_content.inputSectionContent("new title", "new description");
            expect(artifact_section_content.editable_title.value).toBe("new title");
            expect(artifact_section_content.editable_description.value).toBe("new description");
        });

        it("should update the editable title and the editable description of the freetext section", () => {
            freetext_section_content.inputSectionContent("new title", "new description");
            expect(freetext_section_content.editable_title.value).toBe("new title");
            expect(freetext_section_content.editable_description.value).toBe("new description");
        });

        describe("when the user types something in the artidoc-section-title", () => {
            it("should display actions buttons of the artifact section", () => {
                artifact_section_content.inputSectionContent(
                    "new title",
                    artifact_section.value.description.value,
                );

                expect(artifact_section_content.editable_title.value).toBe("new title");
                expect(artifact_section_content.editable_description.value).toBe(
                    artifact_section.value.description.value,
                );
                expect(callbacks.showActionsButtons).toHaveBeenCalledOnce();
            });

            it("should display actions buttons of the freetext section", () => {
                freetext_section_content.inputSectionContent(
                    "new title",
                    freetext_section.value.description,
                );

                expect(freetext_section_content.editable_title.value).toBe("new title");
                expect(freetext_section_content.editable_description.value).toBe(
                    freetext_section.value.description,
                );
                expect(callbacks.showActionsButtons).toHaveBeenCalledOnce();
            });
        });

        describe("when the user types something in the artidoc-section-description", () => {
            it("should display actions buttons of the artifact section", () => {
                artifact_section_content.inputSectionContent(
                    artifact_section.value.title.value,
                    "new description",
                );

                expect(artifact_section_content.editable_description.value).toBe("new description");
                expect(artifact_section_content.editable_title.value).toBe(
                    artifact_section.value.title.value,
                );
                expect(callbacks.showActionsButtons).toHaveBeenCalledOnce();
            });
            it("should display actions buttons of the freetext section", () => {
                freetext_section_content.inputSectionContent(
                    freetext_section.value.title,
                    "new description",
                );

                expect(freetext_section_content.editable_description.value).toBe("new description");
                expect(freetext_section_content.editable_title.value).toBe(
                    freetext_section.value.title,
                );
                expect(callbacks.showActionsButtons).toHaveBeenCalledOnce();
            });
        });

        describe("when the user focuses the editor but no change has occurred", () => {
            it("should hide actions buttons of the artifact section", () => {
                artifact_section_content.inputSectionContent(
                    artifact_section.value.title.value,
                    artifact_section.value.description.value,
                );

                expect(callbacks.hideActionsButtons).toHaveBeenCalledOnce();
            });

            it("should hide actions buttons of the freetext section", () => {
                freetext_section_content.inputSectionContent(
                    freetext_section.value.title,
                    freetext_section.value.description,
                );

                expect(callbacks.hideActionsButtons).toHaveBeenCalledOnce();
            });
        });
    });

    describe("reset_content", () => {
        it("should reset description and title of the artifact section to the original state", () => {
            artifact_section_content.editable_title.value = "new title";
            artifact_section_content.editable_description.value = "new description";
            expect(artifact_section_content.editable_title.value).toEqual("new title");
            expect(artifact_section_content.editable_description.value).toEqual("new description");

            artifact_section_content.resetContent();

            expect(artifact_section_content.editable_title.value).toEqual(
                artifact_section.value.display_title,
            );
            expect(artifact_section_content.editable_description.value).toEqual(
                artifact_section.value.description.value,
            );
        });

        it("should reset description and title of the freetext section to the original state", () => {
            freetext_section_content.editable_title.value = "new title";
            freetext_section_content.editable_description.value = "new description";
            expect(freetext_section_content.editable_title.value).toEqual("new title");
            expect(freetext_section_content.editable_description.value).toEqual("new description");

            freetext_section_content.resetContent();

            expect(freetext_section_content.editable_title.value).toEqual(
                freetext_section.value.display_title,
            );
            expect(freetext_section_content.editable_description.value).toEqual(
                freetext_section.value.description,
            );
        });
    });
});
