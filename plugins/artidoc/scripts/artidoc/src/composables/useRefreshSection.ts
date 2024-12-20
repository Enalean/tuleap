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
import { isArtifactSection, isFreetextSection } from "@/helpers/artidoc-section.type";
import type {
    ArtifactSection,
    ArtidocSection,
    FreetextSection,
} from "@/helpers/artidoc-section.type";
import { getSection } from "@/helpers/rest-querier";
import type { Fault } from "@tuleap/fault";
import type { EditorErrors } from "@/composables/useEditorErrors";
import { TEMPORARY_FLAG_DURATION_IN_MS } from "@/composables/temporary-flag-duration";
import type { Ref } from "vue";
import { ref } from "vue";

export type RefreshSection = {
    isJustRefreshed: () => boolean;
    refreshSection: () => void;
};

export function useRefreshSection(
    section: ArtidocSection,
    editor_errors: EditorErrors,
    callbacks: {
        closeEditor: () => void;
        updateSectionStore: (section: ArtifactSection | FreetextSection) => void;
        updateCurrentSection: (section: ArtidocSection) => void;
    },
): RefreshSection {
    const is_just_refreshed: Ref<boolean> = ref(false);

    function refreshSection(): void {
        if (!isArtifactSection(section) && !isFreetextSection(section)) {
            return;
        }

        getSection(section.id).match(
            (artidoc_section: ArtidocSection) => {
                callbacks.updateCurrentSection(artidoc_section);
                if (isArtifactSection(artidoc_section) || isFreetextSection(artidoc_section)) {
                    callbacks.updateSectionStore(artidoc_section);
                }
                callbacks.closeEditor();
                addTemporaryJustRefreshedFlag();
            },
            (fault: Fault) => {
                editor_errors.handleError(fault);
                editor_errors.is_outdated.value = false;
            },
        );
    }

    function addTemporaryJustRefreshedFlag(): void {
        is_just_refreshed.value = true;
        setTimeout(() => {
            is_just_refreshed.value = false;
        }, TEMPORARY_FLAG_DURATION_IN_MS);
    }

    return {
        isJustRefreshed: () => is_just_refreshed.value,
        refreshSection,
    };
}
