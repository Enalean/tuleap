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
import { putArtifactDescription } from "@/helpers/rest-querier";
import { parse } from "marked";
import type {
    ArtidocSection,
    ArtifactFieldValueCommonmarkRepresentation,
    ArtifactTextFieldValueRepresentation,
} from "@/helpers/artidoc-section.type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";

export type use_section_editor_actions_type = {
    setEditMode: (new_value: boolean) => void;
    saveEditor: () => void;
    cancelEditor: () => void;
};
export type use_section_editor_type = {
    is_section_editable: ComputedRef<boolean>;
    getIsEditMode: () => Ref<boolean>;
    editor_actions: use_section_editor_actions_type;
    inputCurrentDescription: (new_value: string) => void;
    getEditableDescription: () => Ref<string>;
    getReadonlyDescription: () => Ref<string>;
};
function useSectionEditor(section: ArtidocSection): use_section_editor_type {
    const is_edit_mode = ref(false);
    const original_description = ref(
        isCommonmark(section.description)
            ? parse(section.description.commonmark)
            : section.description.format === "text"
              ? parse(section.description.value)
              : section.description.value,
    );
    const editable_description = ref(original_description.value);
    const readonly_description = ref(section.description.post_processed_value);

    const is_section_editable = computed(() => {
        const can_user_edit_document = strictInject<boolean>(CAN_USER_EDIT_DOCUMENT);
        return section.can_user_edit_section && can_user_edit_document;
    });
    const setEditMode = (new_value: boolean): void => {
        is_edit_mode.value = new_value;
    };

    const saveEditor = (): void => {
        if (editable_description.value !== original_description.value) {
            original_description.value = editable_description.value;
            putArtifactDescription(
                section.artifact.id,
                editable_description.value,
                section.description.field_id,
            );
        }
        setEditMode(false);
    };

    const cancelEditor = (): void => {
        editable_description.value = original_description.value;
        setEditMode(false);
    };

    const inputCurrentDescription = (new_value: string): void => {
        editable_description.value = new_value;
    };

    const getIsEditMode = (): Ref<boolean> => {
        return is_edit_mode;
    };

    const getEditableDescription = (): Ref<string> => {
        return editable_description;
    };

    const getReadonlyDescription = (): Ref<string> => {
        return readonly_description;
    };

    const editor_actions = {
        setEditMode,
        saveEditor,
        cancelEditor,
    };

    return {
        is_section_editable,
        getEditableDescription,
        getReadonlyDescription,
        getIsEditMode,
        editor_actions,
        inputCurrentDescription,
    };
}

function isCommonmark(
    description: ArtifactTextFieldValueRepresentation,
): description is ArtifactFieldValueCommonmarkRepresentation {
    return "commonmark" in description;
}

export default useSectionEditor;
