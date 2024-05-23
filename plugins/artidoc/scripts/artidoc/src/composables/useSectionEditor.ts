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
import { getSection, putArtifactDescription } from "@/helpers/rest-querier";
import { parse } from "marked";
import type {
    ArtidocSection,
    ArtifactFieldValueCommonmarkRepresentation,
    ArtifactTextFieldValueRepresentation,
} from "@/helpers/artidoc-section.type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { preventPageLeave, allowPageLeave } from "@/helpers/on-before-unload";

export type use_section_editor_actions_type = {
    enableEditor: () => void;
    saveEditor: () => void;
    cancelEditor: () => void;
};
export type use_section_editor_type = {
    is_section_editable: ComputedRef<boolean>;
    isSectionInEditMode: () => Ref<boolean>;
    isBeeingSaved: () => Ref<boolean>;
    isJustSaved: () => Ref<boolean>;
    isInError: () => Ref<boolean>;
    editor_actions: use_section_editor_actions_type;
    inputCurrentDescription: (new_value: string) => void;
    getEditableDescription: () => Ref<string>;
    getReadonlyDescription: () => Ref<string>;
    clearGlobalNumberOfOpenEditorForTests: () => void;
};

let nb_active_edit_mode = 0;

function useSectionEditor(section: ArtidocSection): use_section_editor_type {
    const current_section: Ref<ArtidocSection> = ref(section);
    const is_edit_mode = ref(false);
    const original_description = ref(
        isCommonmark(current_section.value.description)
            ? parse(current_section.value.description.commonmark)
            : current_section.value.description.format === "text"
              ? parse(current_section.value.description.value)
              : current_section.value.description.value,
    );
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
        if (editable_description.value !== original_description.value) {
            is_being_saved.value = true;
            putArtifactDescription(
                current_section.value.artifact.id,
                editable_description.value,
                current_section.value.description.field_id,
            )
                .andThen(() => getSection(current_section.value.id))
                .match(
                    (artidoc_section: ArtidocSection) => {
                        current_section.value = artidoc_section;
                        setEditMode(false);
                        is_being_saved.value = false;
                        addTemporaryJustSavedFlag();
                    },
                    () => {
                        is_in_error.value = true;
                        is_being_saved.value = false;
                    },
                );
        } else {
            setEditMode(false);
            addTemporaryJustSavedFlag();
        }
    };

    function addTemporaryJustSavedFlag(): void {
        is_just_saved.value = true;
        setTimeout(() => {
            is_just_saved.value = false;
        }, 1000);
    }

    const enableEditor = (): void => {
        setEditMode(true);
    };

    const cancelEditor = (): void => {
        editable_description.value = original_description.value;
        setEditMode(false);
    };

    const inputCurrentDescription = (new_value: string): void => {
        editable_description.value = new_value;
    };

    const isSectionInEditMode = (): Ref<boolean> => {
        return is_edit_mode;
    };

    const isBeeingSaved = (): Ref<boolean> => is_being_saved;
    const isJustSaved = (): Ref<boolean> => is_just_saved;
    const isInError = (): Ref<boolean> => is_in_error;

    const getEditableDescription = (): Ref<string> => {
        return editable_description;
    };

    const getReadonlyDescription = (): Ref<string> => {
        return readonly_description;
    };

    const editor_actions = {
        enableEditor,
        saveEditor,
        cancelEditor,
    };

    return {
        is_section_editable,
        getEditableDescription,
        getReadonlyDescription,
        isSectionInEditMode,
        isBeeingSaved,
        isJustSaved,
        isInError,
        editor_actions,
        inputCurrentDescription,
        clearGlobalNumberOfOpenEditorForTests: (): void => {
            nb_active_edit_mode = 0;
        },
    };
}

function isCommonmark(
    description: ArtifactTextFieldValueRepresentation,
): description is ArtifactFieldValueCommonmarkRepresentation {
    return "commonmark" in description;
}

export default useSectionEditor;
