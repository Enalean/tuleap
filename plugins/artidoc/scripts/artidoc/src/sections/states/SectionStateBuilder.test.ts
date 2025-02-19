/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import { ref } from "vue";
import type { Ref } from "vue";
import { v4 as uuidv4 } from "uuid";
import type { SectionState } from "@/sections/states/SectionStateBuilder";
import { getSectionStateBuilder } from "@/sections/states/SectionStateBuilder";
import type { OnGoingUploadFileWithId } from "@/sections/attachments/FileUploadsCollection";
import type { ArtidocSection, ArtifactSection } from "@/helpers/artidoc-section.type";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";

describe("SectionStateBuilder", () => {
    let can_user_edit_document: boolean, pending_uploads: Ref<OnGoingUploadFileWithId[]>;

    beforeEach(() => {
        can_user_edit_document = true;
        pending_uploads = ref([]);
    });

    const createState = (section: ArtidocSection): SectionState =>
        getSectionStateBuilder(can_user_edit_document, pending_uploads).forSection(
            ReactiveStoredArtidocSectionStub.fromSection(section),
        );

    describe("is_image_upload_allowed", () => {
        it("should be false when the section is a freetext section", () => {
            expect(createState(FreetextSectionFactory.create()).is_image_upload_allowed.value).toBe(
                false,
            );
        });

        it("should be false when no file can be attached to the section (%o)", () => {
            const section = ArtifactSectionFactory.override({
                attachments: null,
            });

            expect(createState(section).is_image_upload_allowed.value).toBe(false);
        });

        it("should be true when files can be attached to the section", () => {
            const section = ArtifactSectionFactory.override({
                attachments: {
                    upload_url: "/upload/1015",
                    attachment_ids: [],
                },
            } as Partial<ArtifactSection>);

            expect(createState(section).is_image_upload_allowed.value).toBe(true);
        });
    });

    describe("is_section_editable", () => {
        it.each([
            ["artifact section", ArtifactSectionFactory.create()],
            ["pending artifact section", PendingArtifactSectionFactory.create()],
            ["freetext section", FreetextSectionFactory.create()],
            ["pending freetext section", FreetextSectionFactory.pending()],
        ])(
            "When the section is a %s, then it should return the value of can_user_edit_document",
            (section_type, section) => {
                can_user_edit_document = true;
                expect(createState(section).is_section_editable.value).toBe(true);

                can_user_edit_document = false;
                expect(createState(section).is_section_editable.value).toBe(false);
            },
        );

        it("When the section is an artifact section and the user can edit it, then it should return the value of can_user_edit_document", () => {
            const section = ArtifactSectionFactory.override({ can_user_edit_section: true });

            can_user_edit_document = true;
            expect(createState(section).is_section_editable.value).toBe(true);

            can_user_edit_document = false;
            expect(createState(section).is_section_editable.value).toBe(false);
        });

        it("When the section is an artifact section and the user cannot edit it, then it should return false", () => {
            const section = ArtifactSectionFactory.override({ can_user_edit_section: false });

            expect(createState(section).is_section_editable.value).toBe(false);
        });
    });

    describe("is_save_allowed", () => {
        it("should be true when there is no pending upload for the current section", () => {
            const section = FreetextSectionFactory.create();
            const state = createState(section);

            expect(state.is_save_allowed.value).toBe(true);
        });

        it("should be false when there are not finished pending uploads for the current section", () => {
            const section = FreetextSectionFactory.create();
            const state = createState(section);

            pending_uploads.value.push({
                file_id: uuidv4(),
                file_name: "test.png",
                progress: 10,
                section_id: section.id,
            });
            expect(state.is_save_allowed.value).toBe(false);
        });

        it("should be true when there are only finished pending uploads for the current section", () => {
            const section = FreetextSectionFactory.create();
            const state = createState(section);

            pending_uploads.value.push({
                file_id: uuidv4(),
                file_name: "test.png",
                progress: 100,
                section_id: section.id,
            });
            expect(state.is_save_allowed.value).toBe(true);
        });
    });

    describe("is_section_in_edit_mode", () => {
        it("should be true by default if the section is pending", () => {
            expect(
                createState(FreetextSectionFactory.pending()).is_section_in_edit_mode.value,
            ).toBe(true);
            expect(
                createState(PendingArtifactSectionFactory.create()).is_section_in_edit_mode.value,
            ).toBe(true);
        });

        it("should be false by default if the section is not pending", () => {
            expect(createState(FreetextSectionFactory.create()).is_section_in_edit_mode.value).toBe(
                false,
            );
            expect(createState(ArtifactSectionFactory.create()).is_section_in_edit_mode.value).toBe(
                false,
            );
        });
    });

    describe("is_just_refreshed", () => {
        it("should be false by default", () => {
            expect(createState(FreetextSectionFactory.create()).is_just_refreshed.value).toBe(
                false,
            );
        });
    });

    describe("is_being_saved", () => {
        it("should be false by default", () => {
            expect(createState(FreetextSectionFactory.create()).is_being_saved.value).toBe(false);
        });
    });

    describe("is_just_saved", () => {
        it("should be false by default", () => {
            expect(createState(FreetextSectionFactory.create()).is_just_saved.value).toBe(false);
        });
    });

    describe("is_in_error", () => {
        it("should be false by default", () => {
            expect(createState(FreetextSectionFactory.create()).is_in_error.value).toBe(false);
        });
    });

    describe("is_outdated", () => {
        it("should be false by default", () => {
            expect(createState(FreetextSectionFactory.create()).is_outdated.value).toBe(false);
        });
    });

    describe("is_not_found", () => {
        it("should be false by default", () => {
            expect(createState(FreetextSectionFactory.create()).is_not_found.value).toBe(false);
        });
    });

    describe("error_message", () => {
        it("should be an empty string by default", () => {
            expect(createState(FreetextSectionFactory.create()).error_message.value).toBe("");
        });
    });

    describe("edited_title", () => {
        it.each([
            ["an artifact section", ArtifactSectionFactory.create()],
            ["a pending artifact section", PendingArtifactSectionFactory.create()],
            ["a freetext section", FreetextSectionFactory.create()],
            ["a pending freetext section", FreetextSectionFactory.pending()],
        ])(
            "When the section is %s, then it should have the section's display title as default value",
            (section_type, section) => {
                expect(createState(section).edited_title.value).toBe(section.title);
            },
        );
    });

    describe("edited_description", () => {
        it.each([
            ["an artifact section", ArtifactSectionFactory.create()],
            ["a pending artifact section", PendingArtifactSectionFactory.create()],
        ])(
            "When the section is %s, then it should have the section's HTML description as default value",
            (section_type, section) => {
                expect(createState(section).edited_description.value).toBe(section.description);
            },
        );

        it.each([
            ["a freetext section", FreetextSectionFactory.create()],
            ["a pending freetext section", FreetextSectionFactory.pending()],
        ])(
            "When the section is %s, then it should have the section's HTML description as default value",
            (section_type, section) => {
                expect(createState(section).edited_description.value).toBe(section.description);
            },
        );
    });

    describe("is_editor_reset_needed", () => {
        it("should be false by default", () => {
            expect(createState(FreetextSectionFactory.create()).is_editor_reset_needed.value).toBe(
                false,
            );
        });
    });
});
