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

import { isArtifactSection, isPendingArtifactSection } from "@/helpers/artidoc-section.type";
import type {
    ArtidocSection,
    ArtifactSection,
    PendingArtifactSection,
} from "@/helpers/artidoc-section.type";
import { createSection, getSection, postArtifact, putArtifact } from "@/helpers/rest-querier";
import { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import { errAsync, okAsync } from "neverthrow";
import { getSectionInItsLatestVersion } from "@/helpers/get-section-in-its-latest-version";
import { strictInject } from "@tuleap/vue-strict-inject";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import type { PositionForSave } from "@/stores/useSectionsStore";
import type { EditorErrors } from "@/composables/useEditorErrors";
import type { AttachmentFile } from "@/composables/useAttachmentFile";
import { TEMPORARY_FLAG_DURATION_IN_MS } from "@/composables/temporary-flag-duration";
import { ref } from "vue";

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
    isBeingSaved: () => boolean;
    isJustSaved: () => boolean;
};

export default function useSaveSection(
    editor_errors: EditorErrors,
    callbacks: {
        updateSectionStore: (section: ArtifactSection) => void;
        updateCurrentSection: (section: ArtidocSection) => void;
        closeEditor: () => void;
        setEditMode: (new_value: boolean) => void;
        replacePendingByArtifactSection: (
            pending: PendingArtifactSection,
            section: ArtifactSection,
        ) => void;
        getSectionPositionForSave: (section: ArtidocSection) => PositionForSave;
        mergeArtifactAttachments: AttachmentFile["mergeArtifactAttachments"];
    },
): SaveEditor {
    const is_just_saved = ref(false);
    const is_being_saved = ref(false);
    const document_id = strictInject(DOCUMENT_ID);

    function getLatestVersionOfCurrentSection(
        section: ArtidocSection,
    ): ResultAsync<ArtidocSection, Fault> {
        if (isArtifactSection(section)) {
            return getSection(section.id);
        }

        return okAsync(section);
    }

    function addTemporaryJustSavedFlag(): void {
        is_just_saved.value = true;
        setTimeout(() => {
            is_just_saved.value = false;
        }, TEMPORARY_FLAG_DURATION_IN_MS);
    }

    function forceSave(
        section: ArtidocSection,
        new_value: {
            description: string;
            title: string;
        },
    ): void {
        if (!isArtifactSection(section)) {
            return;
        }

        editor_errors.is_outdated.value = false;
        is_being_saved.value = true;

        putArtifact(
            section.artifact.id,
            new_value.title,
            section.title,
            new_value.description,
            section.description.field_id,
            callbacks.mergeArtifactAttachments(section, new_value.description),
        )
            .andThen(() => getLatestVersionOfCurrentSection(section))
            .match(
                (artidoc_section: ArtidocSection) => {
                    callbacks.updateCurrentSection(artidoc_section);
                    if (isArtifactSection(artidoc_section)) {
                        callbacks.updateSectionStore(artidoc_section);
                    }
                    callbacks.closeEditor();
                    is_being_saved.value = false;
                    addTemporaryJustSavedFlag();
                },
                (fault: Fault) => {
                    editor_errors.handleError(fault);
                    is_being_saved.value = false;
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
        editor_errors.is_in_error.value = false;
        editor_errors.is_outdated.value = false;

        if (
            new_value.description === section.description.value &&
            new_value.title === section.title.value
        ) {
            if (isPendingArtifactSection(section)) {
                return;
            }

            callbacks.setEditMode(false);
            addTemporaryJustSavedFlag();
            return;
        }

        is_being_saved.value = true;

        saveSection(section, { description: new_value.description, title: new_value.title }).match(
            (artidoc_section: ArtidocSection) => {
                if (isPendingArtifactSection(section) && isArtifactSection(artidoc_section)) {
                    callbacks.replacePendingByArtifactSection(section, artidoc_section);
                } else if (isArtifactSection(artidoc_section)) {
                    callbacks.updateSectionStore(artidoc_section);
                }
                callbacks.updateCurrentSection(artidoc_section);

                callbacks.closeEditor();
                is_being_saved.value = false;
                addTemporaryJustSavedFlag();
            },
            (fault: Fault) => {
                editor_errors.handleError(fault);
                is_being_saved.value = false;
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
                createSection(document_id, id, callbacks.getSectionPositionForSave(section)),
            );
        }

        return getSectionInItsLatestVersion(section)
            .andThen(() => {
                if (!isArtifactSection(section)) {
                    return errAsync(
                        Fault.fromMessage("Save of new section is not implemented yet"),
                    );
                }

                return putArtifact(
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
        isJustSaved: () => is_just_saved.value,
        isBeingSaved: () => is_being_saved.value,
    };
}
