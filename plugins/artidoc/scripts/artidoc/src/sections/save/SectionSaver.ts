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

import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import type { ResultAsync } from "neverthrow";
import { okAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { FileIdentifier } from "@tuleap/file-upload";
import {
    isPendingSection,
    isArtifactSection,
    isFreetextSection,
    isPendingArtifactSection,
    isPendingFreetextSection,
} from "@/helpers/artidoc-section.type";
import { createSection, getSection, putSection } from "@/helpers/rest-querier";
import { checkSectionConcurrentEdition } from "@/helpers/CheckSectionConcurrentEdition";
import type { ManageSectionAttachmentFiles } from "@/sections/attachments/SectionAttachmentFilesManager";
import type { ReplacePendingSections } from "@/sections/insert/PendingSectionsReplacer";
import type { UpdateSections } from "@/sections/update/SectionsUpdater";
import type { RetrieveSectionsPositionForSave } from "@/sections/save/SectionsPositionsForSaveRetriever";
import type { SectionState } from "@/sections/states/SectionStateBuilder";
import type { ManageErrorState } from "@/sections/states/SectionErrorManager";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import type { CloseSectionEditor } from "@/sections/editors/SectionEditorCloser";

export type SaveSection = {
    forceSave(): void;
    save(): void;
};

export const getSectionSaver = (
    document_id: number,
    section: ReactiveStoredArtidocSection,
    section_state: SectionState,
    manage_error_state: ManageErrorState,
    replace_pending_sections: ReplacePendingSections,
    update_sections: UpdateSections,
    retrieve_positions: RetrieveSectionsPositionForSave,
    manage_section_attachments: ManageSectionAttachmentFiles,
    close_section_editor: CloseSectionEditor,
): SaveSection => {
    function getLatestVersionOfCurrentSection(): ResultAsync<ArtidocSection, Fault> {
        if (isArtifactSection(section.value) || isFreetextSection(section.value)) {
            return getSection(section.value.id);
        }
        return okAsync(section.value);
    }

    function getAttachementsForSave(
        section: ReactiveStoredArtidocSection,
        edited_description: string,
    ): FileIdentifier[] {
        if (!isFreetextSection(section.value) && section.value.attachments) {
            return manage_section_attachments.mergeArtifactAttachments(
                section.value,
                edited_description,
            );
        }
        return [];
    }

    function forceSave(): void {
        if (!section_state.is_save_allowed.value) {
            return;
        }

        section_state.is_outdated.value = false;
        section_state.is_being_saved.value = true;

        const { edited_title, edited_description } = section_state;

        putSection(
            section.value.id,
            edited_title.value,
            edited_description.value,
            getAttachementsForSave(section, edited_description.value),
            section.value.level,
        )
            .andThen(getLatestVersionOfCurrentSection)
            .match(
                (artidoc_section: ArtidocSection) => {
                    if (isArtifactSection(artidoc_section) || isFreetextSection(artidoc_section)) {
                        update_sections.updateSection(artidoc_section);
                    }
                    section_state.initial_level.value = section.value.level;
                    close_section_editor.closeEditor();
                    section_state.is_being_saved.value = false;
                    section_state.is_just_saved.value = true;
                },
                (fault: Fault) => {
                    manage_error_state.handleError(fault);
                    section_state.is_being_saved.value = false;
                },
            );
    }

    function save(): void {
        if (!section_state.is_save_allowed.value) {
            return;
        }

        manage_error_state.resetErrorStates();
        section_state.is_being_saved.value = true;

        saveSection().match(
            (artidoc_section: ArtidocSection) => {
                if (isPendingSection(section.value)) {
                    replace_pending_sections.replacePendingSection(section.value, artidoc_section);
                } else if (
                    isArtifactSection(artidoc_section) ||
                    isFreetextSection(artidoc_section)
                ) {
                    update_sections.updateSection(artidoc_section);
                }
                section_state.initial_level.value = section.value.level;
                close_section_editor.closeEditor();
                section_state.is_being_saved.value = false;
                section_state.is_just_saved.value = true;
            },
            (fault: Fault) => {
                manage_error_state.handleError(fault);
                section_state.is_being_saved.value = false;
            },
        );
    }

    function saveSection(): ResultAsync<ArtidocSection, Fault> {
        const { edited_title, edited_description } = section_state;

        if (isPendingArtifactSection(section.value)) {
            return createSection(
                document_id,
                edited_title.value,
                edited_description.value,
                retrieve_positions.getSectionPositionForSave(section.value),
                section.value.level,
                "artifact",
                getAttachementsForSave(section, edited_description.value),
            );
        }

        if (isPendingFreetextSection(section.value)) {
            return createSection(
                document_id,
                edited_title.value,
                edited_description.value,
                retrieve_positions.getSectionPositionForSave(section.value),
                section.value.level,
                "freetext",
                getAttachementsForSave(section, edited_description.value),
            );
        }

        return checkSectionConcurrentEdition(section.value)
            .andThen(() =>
                putSection(
                    section.value.id,
                    edited_title.value,
                    edited_description.value,
                    getAttachementsForSave(section, edited_description.value),
                    section.value.level,
                ),
            )
            .andThen(getLatestVersionOfCurrentSection);
    }

    return {
        forceSave,
        save,
    };
};
