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
export type use_section_editor_actions_type = {
    enableEditor: () => void;
    disableEditor: () => void;
    saveEditor: () => void;
    cancelEditor: () => void;
};
export type use_section_editor_type = {
    is_edit_mode: Ref<boolean>;
    editor_actions: use_section_editor_actions_type;
    inputCurrentDescription: (new_value: string) => void;
    editable_description: Ref<string>;
};
function useSectionEditor(description: string, artifact_id: number): use_section_editor_type {
    const is_edit_mode = ref(false);
    const original_description = ref(description);
    const editable_description = ref(original_description.value);
    const enableEditor = (): void => {
        is_edit_mode.value = true;
    };
    const disableEditor = (): void => {
        is_edit_mode.value = false;
    };
    const saveEditor = (): void => {
        original_description.value = editable_description.value;
        putArtifactDescription(artifact_id, editable_description.value);
        disableEditor();
    };
    const cancelEditor = (): void => {
        editable_description.value = original_description.value;
        disableEditor();
    };
    const inputCurrentDescription = (new_value: string): void => {
        editable_description.value = new_value;
    };
    const editor_actions = {
        enableEditor,
        disableEditor,
        saveEditor,
        cancelEditor,
    };
    return {
        editable_description,
        is_edit_mode,
        editor_actions,
        inputCurrentDescription,
    };
}
export default useSectionEditor;
