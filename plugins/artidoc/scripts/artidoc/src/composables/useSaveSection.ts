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
import {
    isPendingSection,
    isArtifactSection,
    isFreetextSection,
    isPendingArtifactSection,
    isPendingFreetextSection,
    isSectionBasedOnArtifact,
} from "@/helpers/artidoc-section.type";
import {
    createArtifactSection,
    createFreetextSection,
    getSection,
    postArtifact,
    putArtifact,
    putSection,
} from "@/helpers/rest-querier";
import { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import { errAsync, okAsync } from "neverthrow";
import { getSectionInItsLatestVersion } from "@/helpers/get-section-in-its-latest-version";
import { strictInject } from "@tuleap/vue-strict-inject";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import type { AttachmentFile } from "@/composables/useAttachmentFile";
import type { ReplacePendingSections } from "@/sections/PendingSectionsReplacer";
import type { UpdateSections } from "@/sections/SectionsUpdater";
import type { RetrieveSectionsPositionForSave } from "@/sections/SectionsPositionsForSaveRetriever";
import type { SectionState } from "@/sections/SectionStateBuilder";
import type { ManageErrorState } from "@/sections/SectionErrorManager";

export type SaveEditor = {
    forceSave: (
        section: ArtidocSection,
        new_value: {
            description: string;
            title: string;
        },
    ) => void;
    save: (
        section: ArtidocSection,
        new_value: {
            description: string;
            title: string;
        },
    ) => void;
};

export default function useSaveSection(
    section_state: SectionState,
    manage_error_state: ManageErrorState,
    replace_pending_sections: ReplacePendingSections,
    update_sections: UpdateSections,
    retrieve_positions: RetrieveSectionsPositionForSave,
    callbacks: {
        closeEditor: () => void;
        mergeArtifactAttachments: AttachmentFile["mergeArtifactAttachments"];
    },
): SaveEditor {
    const document_id = strictInject(DOCUMENT_ID);

    function getLatestVersionOfCurrentSection(
        section: ArtidocSection,
    ): ResultAsync<ArtidocSection, Fault> {
        if (isArtifactSection(section) || isFreetextSection(section)) {
            return getSection(section.id);
        }

        return okAsync(section);
    }

    function forceSave(
        section: ArtidocSection,
        new_value: {
            description: string;
            title: string;
        },
    ): void {
        if (!isArtifactSection(section) && !isFreetextSection(section)) {
            return;
        }

        section_state.is_outdated.value = false;
        section_state.is_being_saved.value = true;

        const put = isFreetextSection(section)
            ? putSection(section.id, new_value.title, new_value.description)
            : putArtifact(
                  section.artifact.id,
                  new_value.title,
                  section.title,
                  new_value.description,
                  section.description.field_id,
                  callbacks.mergeArtifactAttachments(section, new_value.description),
              );
        put.andThen(() => getLatestVersionOfCurrentSection(section)).match(
            (artidoc_section: ArtidocSection) => {
                if (isArtifactSection(artidoc_section) || isFreetextSection(artidoc_section)) {
                    update_sections.updateSection(artidoc_section);
                }
                callbacks.closeEditor();
                section_state.is_being_saved.value = false;
                section_state.is_just_saved.value = true;
            },
            (fault: Fault) => {
                manage_error_state.handleError(fault);
                section_state.is_being_saved.value = false;
            },
        );
    }

    const save = (
        section: ArtidocSection,
        new_value: {
            description: string;
            title: string;
        },
    ): void => {
        manage_error_state.resetErrorStates();

        if (
            isSectionBasedOnArtifact(section) &&
            new_value.description === section.description.value &&
            new_value.title === section.title.value
        ) {
            if (isPendingArtifactSection(section)) {
                return;
            }

            section_state.is_section_in_edit_mode.value = false;
            section_state.is_just_saved.value = true;
            return;
        }

        if (
            isFreetextSection(section) &&
            new_value.description === section.description &&
            new_value.title === section.title
        ) {
            section_state.is_section_in_edit_mode.value = false;
            section_state.is_just_saved.value = true;
            return;
        }

        section_state.is_being_saved.value = true;

        saveSection(section, { description: new_value.description, title: new_value.title }).match(
            (artidoc_section: ArtidocSection) => {
                if (isPendingSection(section)) {
                    replace_pending_sections.replacePendingSection(section, artidoc_section);
                } else if (
                    isArtifactSection(artidoc_section) ||
                    isFreetextSection(artidoc_section)
                ) {
                    update_sections.updateSection(artidoc_section);
                }

                callbacks.closeEditor();
                section_state.is_being_saved.value = false;
                section_state.is_just_saved.value = true;
            },
            (fault: Fault) => {
                manage_error_state.handleError(fault);
                section_state.is_being_saved.value = false;
            },
        );
    };

    function saveSection(
        section: ArtidocSection,
        new_value: {
            description: string;
            title: string;
        },
    ): ResultAsync<ArtidocSection, Fault> {
        if (isPendingArtifactSection(section)) {
            const merged_attachments = callbacks.mergeArtifactAttachments(
                section,
                new_value.description,
            );
            return postArtifact(
                section.tracker,
                new_value.title,
                section.title,
                new_value.description,
                section.description.field_id,
                merged_attachments,
            ).andThen(({ id }) =>
                createArtifactSection(
                    document_id,
                    id,
                    retrieve_positions.getSectionPositionForSave(section),
                ),
            );
        }

        if (isPendingFreetextSection(section)) {
            return createFreetextSection(
                document_id,
                new_value.title,
                new_value.description,
                retrieve_positions.getSectionPositionForSave(section),
            );
        }

        return getSectionInItsLatestVersion(section)
            .andThen(() => {
                if (!isArtifactSection(section) && !isFreetextSection(section)) {
                    return errAsync(
                        Fault.fromMessage("Save of new section is not implemented yet"),
                    );
                }

                return isFreetextSection(section)
                    ? putSection(section.id, new_value.title, new_value.description)
                    : putArtifact(
                          section.artifact.id,
                          new_value.title,
                          section.title,
                          new_value.description,
                          section.description.field_id,
                          callbacks.mergeArtifactAttachments(section, new_value.description),
                      );
            })
            .andThen(() => getLatestVersionOfCurrentSection(section));
    }

    return {
        forceSave,
        save,
    };
}
