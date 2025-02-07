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
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import {
    isPendingSection,
    isArtifactSection,
    isFreetextSection,
    isPendingArtifactSection,
    isPendingFreetextSection,
} from "@/helpers/artidoc-section.type";
import {
    createArtifactSection,
    createFreetextSection,
    getSection,
    postArtifact,
    putArtifact,
    putSection,
} from "@/helpers/rest-querier";
import { getSectionInItsLatestVersion } from "@/helpers/get-section-in-its-latest-version";
import type { ManageSectionAttachmentFiles } from "@/sections/SectionAttachmentFilesManager";
import type { ReplacePendingSections } from "@/sections/PendingSectionsReplacer";
import type { UpdateSections } from "@/sections/SectionsUpdater";
import type { RetrieveSectionsPositionForSave } from "@/sections/SectionsPositionsForSaveRetriever";
import type { SectionState } from "@/sections/SectionStateBuilder";
import type { ManageErrorState } from "@/sections/SectionErrorManager";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import type { CloseSectionEditor } from "@/sections/SectionEditorCloser";

export type SaveEditor = {
    forceSave: () => void;
    save: () => void;
};

export default function useSaveSection(
    document_id: number,
    section: ReactiveStoredArtidocSection,
    section_state: SectionState,
    manage_error_state: ManageErrorState,
    replace_pending_sections: ReplacePendingSections,
    update_sections: UpdateSections,
    retrieve_positions: RetrieveSectionsPositionForSave,
    manage_section_attachments: ManageSectionAttachmentFiles,
    close_section_editor: CloseSectionEditor,
): SaveEditor {
    function getLatestVersionOfCurrentSection(): ResultAsync<ArtidocSection, Fault> {
        if (isArtifactSection(section.value) || isFreetextSection(section.value)) {
            return getSection(section.value.id);
        }

        return okAsync(section.value);
    }

    function forceSave(): void {
        if (!isArtifactSection(section.value) && !isFreetextSection(section.value)) {
            return;
        }

        section_state.is_outdated.value = false;
        section_state.is_being_saved.value = true;

        const { edited_title, edited_description } = section_state;

        const put = isFreetextSection(section.value)
            ? putSection(section.value.id, edited_title.value, edited_description.value)
            : putArtifact(
                  section.value.artifact.id,
                  edited_title.value,
                  section.value.title,
                  edited_description.value,
                  section.value.description.field_id,
                  manage_section_attachments.mergeArtifactAttachments(
                      section.value,
                      edited_description.value,
                  ),
              );
        put.andThen(() => getLatestVersionOfCurrentSection()).match(
            (artidoc_section: ArtidocSection) => {
                if (isArtifactSection(artidoc_section) || isFreetextSection(artidoc_section)) {
                    update_sections.updateSection(artidoc_section);
                }
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

    const save = (): void => {
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

                close_section_editor.closeEditor();
                section_state.is_being_saved.value = false;
                section_state.is_just_saved.value = true;
            },
            (fault: Fault) => {
                manage_error_state.handleError(fault);
                section_state.is_being_saved.value = false;
            },
        );
    };

    function saveSection(): ResultAsync<ArtidocSection, Fault> {
        const { edited_title, edited_description } = section_state;

        if (isPendingArtifactSection(section.value)) {
            const merged_attachments = manage_section_attachments.mergeArtifactAttachments(
                section.value,
                edited_description.value,
            );
            return postArtifact(
                section.value.tracker,
                edited_title.value,
                section.value.title,
                edited_description.value,
                section.value.description.field_id,
                merged_attachments,
            ).andThen(({ id }) =>
                createArtifactSection(
                    document_id,
                    id,
                    retrieve_positions.getSectionPositionForSave(section.value),
                ),
            );
        }

        if (isPendingFreetextSection(section.value)) {
            return createFreetextSection(
                document_id,
                edited_title.value,
                edited_description.value,
                retrieve_positions.getSectionPositionForSave(section.value),
            );
        }

        return getSectionInItsLatestVersion(section.value)
            .andThen(() => {
                if (!isArtifactSection(section.value) && !isFreetextSection(section.value)) {
                    return errAsync(
                        Fault.fromMessage("Save of new section is not implemented yet"),
                    );
                }

                return isFreetextSection(section.value)
                    ? putSection(section.value.id, edited_title.value, edited_description.value)
                    : putArtifact(
                          section.value.artifact.id,
                          edited_title.value,
                          section.value.title,
                          edited_description.value,
                          section.value.description.field_id,
                          manage_section_attachments.mergeArtifactAttachments(
                              section.value,
                              edited_description.value,
                          ),
                      );
            })
            .andThen(() => getLatestVersionOfCurrentSection());
    }

    return {
        forceSave,
        save,
    };
}
