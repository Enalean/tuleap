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
import { getSection, putArtifact } from "@/helpers/rest-querier";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { preventPageLeave, allowPageLeave } from "@/helpers/on-before-unload";
import { convertDescriptionToHtml } from "@/helpers/convert-description-to-html";
import {
    isOutdatedSectionFault,
    isSectionInItsLatestVersion,
} from "@/helpers/is-section-in-its-latest-version";
import type { Fault } from "@tuleap/fault";

export type use_section_editor_actions_type = {
    enableEditor: () => void;
    saveEditor: () => void;
    forceSaveEditor: () => void;
    cancelEditor: () => void;
    refreshSection: () => void;
};

export type use_section_editor_type = {
    is_section_editable: ComputedRef<boolean>;
    isSectionInEditMode: () => Ref<boolean>;
    isBeeingSaved: () => Ref<boolean>;
    isJustSaved: () => Ref<boolean>;
    isInError: () => Ref<boolean>;
    isOutdated: () => Ref<boolean>;
    isNotFoundError: () => Ref<boolean>;
    editor_actions: use_section_editor_actions_type;
    inputCurrentTitle: (new_value: string) => void;
    inputCurrentDescription: (new_value: string) => void;
    getEditableTitle: () => Ref<string>;
    getEditableDescription: () => Ref<string>;
    getReadonlyDescription: () => Ref<string>;
    clearGlobalNumberOfOpenEditorForTests: () => void;
};

let nb_active_edit_mode = 0;

function useSectionEditor(
    section: ArtidocSection,
    update_section_callback: (section: ArtidocSection) => void,
): use_section_editor_type {
    const current_section: Ref<ArtidocSection> = ref(section);
    const is_edit_mode = ref(false);
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
        const can_user_edit_document = strictInject<boolean>(CAN_USER_EDIT_DOCUMENT);
        return section.can_user_edit_section && can_user_edit_document;
    });
    const is_being_saved = ref(false);
    const is_just_saved = ref(false);
    const is_in_error = ref(false);
    const is_outdated = ref(false);
    const is_not_found = ref(false);

    const setEditMode = (new_value: boolean): void => {
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
        is_being_saved.value = true;

        if (
            editable_description.value === original_description.value &&
            editable_title.value === original_title.value
        ) {
            setEditMode(false);
            addTemporaryJustSavedFlag();
            return;
        }

        isSectionInItsLatestVersion(current_section.value)
            .andThen(() =>
                putArtifact(
                    current_section.value.artifact.id,
                    editable_title.value,
                    current_section.value.title,
                    editable_description.value,
                    current_section.value.description.field_id,
                ),
            )
            .andThen(() => getSection(current_section.value.id))
            .match(
                (artidoc_section: ArtidocSection) => {
                    current_section.value = artidoc_section;
                    update_section_callback(artidoc_section);
                    cancelEditor();
                    is_being_saved.value = false;
                    addTemporaryJustSavedFlag();
                },
                (fault: Fault) => {
                    handleError(fault);
                    is_being_saved.value = false;
                },
            );
    };

    function forceSaveEditor(): void {
        is_outdated.value = false;
        is_being_saved.value = true;

        putArtifact(
            current_section.value.artifact.id,
            editable_title.value,
            current_section.value.title,
            editable_description.value,
            current_section.value.description.field_id,
        )
            .andThen(() => getSection(current_section.value.id))
            .match(
                (artidoc_section: ArtidocSection) => {
                    current_section.value = artidoc_section;
                    update_section_callback(artidoc_section);
                    cancelEditor();
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
        getSection(current_section.value.id).match(
            (artidoc_section: ArtidocSection) => {
                current_section.value = artidoc_section;
                update_section_callback(artidoc_section);
                cancelEditor();
                addTemporaryJustSavedFlag();
                is_outdated.value = false;
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
        }, 1000);
    }

    const enableEditor = (): void => {
        setEditMode(true);
    };

    function cancelEditor(): void {
        editable_description.value = original_description.value;
        editable_title.value = original_title.value;
        setEditMode(false);
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
    const isInError = (): Ref<boolean> => is_in_error;
    const isOutdated = (): Ref<boolean> => is_outdated;
    const isNotFoundError = (): Ref<boolean> => is_not_found;

    const getEditableDescription = (): Ref<string> => {
        return editable_description;
    };
    const getEditableTitle = (): Ref<string> => editable_title;

    const getReadonlyDescription = (): Ref<string> => {
        return readonly_description;
    };

    const editor_actions: use_section_editor_actions_type = {
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
        isSectionInEditMode,
        isBeeingSaved,
        isJustSaved,
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

export default useSectionEditor;
