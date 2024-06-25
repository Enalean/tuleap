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
import { computed, ref } from "vue";
import type { Ref, ComputedRef } from "vue";
import { createSection, getSection, postArtifact, putArtifact } from "@/helpers/rest-querier";
import type {
    ArtidocSection,
    ArtifactSection,
    PendingArtifactSection,
} from "@/helpers/artidoc-section.type";
import { isArtifactSection, isPendingArtifactSection } from "@/helpers/artidoc-section.type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { preventPageLeave, allowPageLeave } from "@/helpers/on-before-unload";
import { convertDescriptionToHtml } from "@/helpers/convert-description-to-html";
import {
    isOutdatedSectionFault,
    getSectionInItsLatestVersion,
} from "@/helpers/get-section-in-its-latest-version";
import { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import { errAsync, okAsync } from "neverthrow";
import type { Tracker } from "@/stores/configuration-store";
import type { AttachmentFile } from "@/composables/useAttachmentFile";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import type { PositionForSave } from "@/stores/useSectionsStore";

export type SectionEditorActions = {
    enableEditor: () => void;
    saveEditor: () => void;
    forceSaveEditor: () => void;
    cancelEditor: (tracker: Tracker | null) => void;
    refreshSection: () => void;
};

export type SectionEditor = {
    is_section_editable: ComputedRef<boolean>;
    isSectionInEditMode: () => Ref<boolean>;
    isBeeingSaved: () => Ref<boolean>;
    isJustSaved: () => Ref<boolean>;
    isJustRefreshed: () => Ref<boolean>;
    isInError: () => Ref<boolean>;
    isOutdated: () => Ref<boolean>;
    isNotFoundError: () => Ref<boolean>;
    editor_actions: SectionEditorActions;
    inputCurrentTitle: (new_value: string) => void;
    inputCurrentDescription: (new_value: string) => void;
    getEditableTitle: () => Ref<string>;
    getEditableDescription: () => Ref<string>;
    getReadonlyDescription: () => Ref<string>;
    getErrorMessage: () => Ref<string>;
    clearGlobalNumberOfOpenEditorForTests: () => void;
};

let nb_active_edit_mode = 0;

const TEMPORARY_FLAG_DURATION_IN_MS = 1000;

export function useSectionEditor(
    section: ArtidocSection,
    update_section_callback: (section: ArtifactSection) => void,
    remove_section_callback: (section: ArtidocSection, tracker: Tracker | null) => void,
    get_section_position_callback: (section: ArtidocSection) => PositionForSave,
    replace_pending_by_artifact_section_callback: (
        pending: PendingArtifactSection,
        section: ArtifactSection,
    ) => void,
    merge_artifact_attachments: AttachmentFile["mergeArtifactAttachments"],
    set_waiting_list_attachments: AttachmentFile["setWaitingListAttachments"],
): SectionEditor {
    const document_id = strictInject(DOCUMENT_ID);
    const can_user_edit_document = strictInject(CAN_USER_EDIT_DOCUMENT);

    const current_section: Ref<ArtidocSection> = ref(section);
    const is_edit_mode = ref(isPendingArtifactSection(current_section.value));
    const original_description = computed(() =>
        convertDescriptionToHtml(current_section.value.description),
    );
    const original_title = computed(() => current_section.value.display_title);
    const editable_title = ref(original_title.value);
    const editable_description = ref(original_description.value);
    const readonly_description = computed(
        () => current_section.value.description.post_processed_value,
    );
    const is_section_editable = computed(() => {
        if (isPendingArtifactSection(current_section.value)) {
            return can_user_edit_document;
        }

        if (
            isArtifactSection(current_section.value) &&
            current_section.value.can_user_edit_section
        ) {
            return can_user_edit_document;
        }

        return false;
    });
    const is_being_saved = ref(false);
    const is_just_saved = ref(false);
    const is_just_refreshed = ref(false);
    const is_in_error = ref(false);
    const is_outdated = ref(false);
    const is_not_found = ref(false);
    const error_message = ref("");

    const setEditMode = (new_value: boolean): void => {
        if (is_edit_mode.value === new_value) {
            return;
        }

        is_edit_mode.value = new_value;

        if (new_value) {
            nb_active_edit_mode++;
        } else {
            nb_active_edit_mode = Math.abs(nb_active_edit_mode - 1);
        }

        if (nb_active_edit_mode > 0) {
            preventPageLeave();
        } else {
            allowPageLeave();
        }
    };

    const saveEditor = (): void => {
        is_in_error.value = false;
        is_outdated.value = false;

        if (
            editable_description.value === original_description.value &&
            editable_title.value === original_title.value
        ) {
            if (isPendingArtifactSection(current_section.value)) {
                return;
            }

            setEditMode(false);
            addTemporaryJustSavedFlag();
            return;
        }

        is_being_saved.value = true;

        saveSection().match(
            (artidoc_section: ArtidocSection) => {
                if (
                    isPendingArtifactSection(current_section.value) &&
                    isArtifactSection(artidoc_section)
                ) {
                    replace_pending_by_artifact_section_callback(
                        current_section.value,
                        artidoc_section,
                    );
                } else if (isArtifactSection(artidoc_section)) {
                    update_section_callback(artidoc_section);
                }
                current_section.value = artidoc_section;

                closeEditor();
                is_being_saved.value = false;
                addTemporaryJustSavedFlag();
            },
            (fault: Fault) => {
                handleError(fault);
                is_being_saved.value = false;
            },
        );
    };

    function saveSection(): ResultAsync<ArtidocSection, Fault> {
        if (isPendingArtifactSection(current_section.value)) {
            const merged_attachments = merge_artifact_attachments(
                section,
                editable_description.value,
            );

            return postArtifact(
                current_section.value.tracker,
                editable_title.value,
                current_section.value.title,
                editable_description.value,
                current_section.value.description.field_id,
                merged_attachments,
            ).andThen(({ id }) =>
                createSection(
                    document_id,
                    id,
                    get_section_position_callback(current_section.value),
                ),
            );
        }

        return getSectionInItsLatestVersion(current_section.value)
            .andThen((section_in_its_latest_version: ArtidocSection) => {
                if (!isArtifactSection(current_section.value)) {
                    return errAsync(
                        Fault.fromMessage("Save of new section is not implemented yet"),
                    );
                }

                const merged_attachments = merge_artifact_attachments(
                    section_in_its_latest_version,
                    editable_description.value,
                );

                return putArtifact(
                    current_section.value.artifact.id,
                    editable_title.value,
                    current_section.value.title,
                    editable_description.value,
                    current_section.value.description.field_id,
                    merged_attachments,
                );
            })
            .andThen(getLatestVersionOfCurrentSection);
    }

    function getLatestVersionOfCurrentSection(): ResultAsync<ArtidocSection, Fault> {
        if (isArtifactSection(current_section.value)) {
            return getSection(current_section.value.id);
        }

        return okAsync(current_section.value);
    }

    function forceSaveEditor(): void {
        if (!isArtifactSection(current_section.value)) {
            return;
        }

        is_outdated.value = false;
        is_being_saved.value = true;

        putArtifact(
            current_section.value.artifact.id,
            editable_title.value,
            current_section.value.title,
            editable_description.value,
            current_section.value.description.field_id,
            merge_artifact_attachments(current_section.value, editable_description.value),
        )
            .andThen(getLatestVersionOfCurrentSection)
            .match(
                (artidoc_section: ArtidocSection) => {
                    current_section.value = artidoc_section;
                    if (isArtifactSection(artidoc_section)) {
                        update_section_callback(artidoc_section);
                    }
                    closeEditor();

                    is_being_saved.value = false;
                    addTemporaryJustSavedFlag();
                },
                (fault: Fault) => {
                    handleError(fault);
                    is_being_saved.value = false;
                },
            );
    }

    function refreshSection(): void {
        if (!isArtifactSection(current_section.value)) {
            return;
        }

        getSection(current_section.value.id).match(
            (artidoc_section: ArtidocSection) => {
                current_section.value = artidoc_section;
                if (isArtifactSection(artidoc_section)) {
                    update_section_callback(artidoc_section);
                }
                closeEditor();
                addTemporaryJustRefreshedFlag();
            },
            (fault: Fault) => {
                handleError(fault);
                is_outdated.value = false;
            },
        );
    }

    function handleError(fault: Fault): void {
        if (isOutdatedSectionFault(fault)) {
            is_outdated.value = true;
            return;
        }

        is_in_error.value = true;
        if (isNotFound(fault) || isForbidden(fault)) {
            is_not_found.value = true;
        }
        error_message.value = String(fault);
    }

    function isNotFound(fault: Fault): boolean {
        return "isNotFound" in fault && fault.isNotFound() === true;
    }

    function isForbidden(fault: Fault): boolean {
        return "isForbidden" in fault && fault.isForbidden() === true;
    }

    function addTemporaryJustSavedFlag(): void {
        is_just_saved.value = true;
        setTimeout(() => {
            is_just_saved.value = false;
        }, TEMPORARY_FLAG_DURATION_IN_MS);
    }

    function addTemporaryJustRefreshedFlag(): void {
        is_just_refreshed.value = true;
        setTimeout(() => {
            is_just_refreshed.value = false;
        }, TEMPORARY_FLAG_DURATION_IN_MS);
    }

    const enableEditor = (): void => {
        setEditMode(true);
    };

    function closeEditor(): void {
        editable_description.value = original_description.value;
        editable_title.value = original_title.value;
        set_waiting_list_attachments([]);
        setEditMode(false);
        resetErrorStates();
    }

    function cancelEditor(tracker: Tracker | null): void {
        closeEditor();
        if (isPendingArtifactSection(current_section.value)) {
            remove_section_callback(current_section.value, tracker);
        }
    }

    function resetErrorStates(): void {
        is_outdated.value = false;
        is_in_error.value = false;
        is_not_found.value = false;
    }

    const inputCurrentDescription = (new_value: string): void => {
        editable_description.value = new_value;
    };

    const isSectionInEditMode = (): Ref<boolean> => is_edit_mode;

    const inputCurrentTitle = (new_value: string): void => {
        editable_title.value = new_value;
    };

    const isBeeingSaved = (): Ref<boolean> => is_being_saved;
    const isJustSaved = (): Ref<boolean> => is_just_saved;
    const isJustRefreshed = (): Ref<boolean> => is_just_refreshed;
    const isInError = (): Ref<boolean> => is_in_error;
    const isOutdated = (): Ref<boolean> => is_outdated;
    const isNotFoundError = (): Ref<boolean> => is_not_found;
    const getErrorMessage = (): Ref<string> => error_message;

    const getEditableDescription = (): Ref<string> => {
        return editable_description;
    };
    const getEditableTitle = (): Ref<string> => editable_title;

    const getReadonlyDescription = (): Ref<string> => {
        return readonly_description;
    };

    const editor_actions: SectionEditorActions = {
        enableEditor,
        saveEditor,
        forceSaveEditor,
        cancelEditor,
        refreshSection,
    };

    return {
        is_section_editable,
        getEditableTitle,
        getEditableDescription,
        getReadonlyDescription,
        getErrorMessage,
        isSectionInEditMode,
        isBeeingSaved,
        isJustSaved,
        isJustRefreshed,
        isInError,
        isOutdated,
        isNotFoundError,
        editor_actions,
        inputCurrentTitle,
        inputCurrentDescription,
        clearGlobalNumberOfOpenEditorForTests: (): void => {
            nb_active_edit_mode = 0;
        },
    };
}
