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

import type { SectionEditor } from "@/composables/useSectionEditor";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";

export const EDITORS_COLLECTION: StrictInjectionKey<SectionEditorsStore> = Symbol("editors-store");

export interface SectionEditorsStore {
    readonly addEditor: (section: ArtidocSection, editor: SectionEditor) => void;
    readonly removeEditor: (section: ArtidocSection) => void;
    readonly hasAtLeastOneEditorOpened: () => boolean;
}

export function useSectionEditorsStore(): SectionEditorsStore {
    const editors: Map<string, SectionEditor> = new Map<string, SectionEditor>();

    function addEditor(section: ArtidocSection, editor: SectionEditor): void {
        editors.set(section.id, editor);
    }

    function removeEditor(section: ArtidocSection): void {
        editors.delete(section.id);
    }

    function hasAtLeastOneEditorOpened(): boolean {
        for (const editor of editors.values()) {
            if (editor.editor_state.is_section_in_edit_mode.value) {
                return true;
            }
        }

        return false;
    }

    return {
        addEditor,
        removeEditor,
        hasAtLeastOneEditorOpened,
    };
}
