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

import type { Ref } from "vue";
import { computed, ref } from "vue";
import { convertDescriptionToHtml } from "@/helpers/convert-description-to-html";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";

export type EditorSectionContent = {
    inputCurrentTitle: (new_value: string) => void;
    inputCurrentDescription: (new_value: string) => void;
    editable_title: Ref<string>;
    editable_description: Ref<string>;
    getReadonlyDescription: () => string;
    resetContent: () => void;
};

export function useEditorSectionContent(section: Ref<ArtidocSection>): EditorSectionContent {
    const original_description = computed(() =>
        convertDescriptionToHtml(section.value.description),
    );
    const original_title = computed(() => section.value.display_title);
    const editable_title = ref(original_title.value);
    const editable_description = ref(original_description.value);
    const readonly_description = computed(() => section.value.description.post_processed_value);

    const inputCurrentDescription = (new_value: string): void => {
        editable_description.value = new_value;
    };

    const inputCurrentTitle = (new_value: string): void => {
        editable_title.value = new_value;
    };

    const resetContent = (): void => {
        editable_title.value = original_title.value;
        editable_description.value = original_description.value;
    };

    return {
        editable_title,
        editable_description,
        getReadonlyDescription: () => readonly_description.value,
        inputCurrentTitle,
        inputCurrentDescription,
        resetContent,
    };
}
