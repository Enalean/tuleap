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

import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";
import type { Ref } from "vue";
import { watch, ref } from "vue";

export const EDITOR_CHOICE: StrictInjectionKey<EditorChoice> = Symbol("editor-choice");

interface EditorChoice {
    readonly has_more_than_one_editor: boolean;
    readonly current_editor: Ref<"legacy" | "nextgen">;
}
export function editorChoice(is_next_gen_editor_enabled: boolean): EditorChoice {
    const KEY = "artidoc-editor-choice";

    const last_use = window.localStorage.getItem(KEY);
    const current_editor: EditorChoice["current_editor"] = ref(
        last_use === "legacy" || last_use === "nextgen" ? last_use : "legacy",
    );

    watch(current_editor, () => {
        window.localStorage.setItem(KEY, current_editor.value);
    });

    return {
        has_more_than_one_editor: is_next_gen_editor_enabled,
        current_editor,
    };
}
