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
import { ref } from "vue";
import type { Ref } from "vue";
import { putArtifactDescription } from "@/helpers/rest-querier";
import type {
    ArtifactFieldValueCommonmarkRepresentation,
    ArtifactTextFieldValueRepresentation,
} from "@/helpers/artidoc-section.type";
import { parse } from "marked";

export type use_section_editor_actions_type = {
    setEditMode: (new_value: boolean) => void;
    saveEditor: () => void;
    cancelEditor: () => void;
};
export type use_section_editor_type = {
    getIsEditMode: () => Ref<boolean>;
    editor_actions: use_section_editor_actions_type;
    inputCurrentDescription: (new_value: string) => void;
    getEditableDescription: () => Ref<string>;
    getReadonlyDescription: () => Ref<string>;
};
function useSectionEditor(
    description: ArtifactTextFieldValueRepresentation,
    artifact_id: number,
): use_section_editor_type {
    const is_edit_mode = ref(false);
    const original_description = ref(
        isCommonmark(description)
            ? parse(description.commonmark)
            : description.format === "text"
              ? parse(description.value)
              : description.value,
    );
    const editable_description = ref(original_description.value);
    const readonly_description = ref(description.post_processed_value);

    const setEditMode = (new_value: boolean): void => {
        is_edit_mode.value = new_value;
    };

    const saveEditor = (): void => {
        if (editable_description.value !== original_description.value) {
            original_description.value = editable_description.value;
            putArtifactDescription(artifact_id, editable_description.value, description.field_id);
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
