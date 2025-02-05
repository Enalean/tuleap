/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { computed, shallowRef, triggerRef } from "vue";
import type { ComputedRef } from "vue";
import type {
    ReactiveStoredArtidocSection,
    StoredArtidocSection,
} from "@/sections/SectionsCollection";
import type { BuildSectionState, SectionState } from "@/sections/SectionStateBuilder";

export type SectionsStatesCollection = {
    createStateForSection(section: ReactiveStoredArtidocSection): void;
    createAllSectionsStates(sections: ReactiveStoredArtidocSection[]): void;
    getSectionState(section: StoredArtidocSection): SectionState;
    destroyAll(): void;
    destroySectionState(section: StoredArtidocSection): void;
    has_at_least_one_section_in_edit_mode: ComputedRef<boolean>;
};

export const getSectionsStatesCollection = (
    build_section_state: BuildSectionState,
): SectionsStatesCollection => {
    const states = shallowRef(new Map<string, SectionState>());

    const createNewState = (section: ReactiveStoredArtidocSection): void => {
        states.value.set(section.value.internal_id, build_section_state.forSection(section));
    };

    return {
        createStateForSection(section: ReactiveStoredArtidocSection): void {
            createNewState(section);
            triggerRef(states);
        },
        createAllSectionsStates(sections: ReactiveStoredArtidocSection[]): void {
            sections.forEach(createNewState);
        },
        getSectionState({ internal_id }): SectionState {
            const state = states.value.get(internal_id);
            if (!state) {
                throw new Error(`No state found for section with internal id #${internal_id}`);
            }
            return state;
        },
        has_at_least_one_section_in_edit_mode: computed(() => {
            return Array.from(states.value.values()).some(
                (state) => state.is_section_in_edit_mode.value === true,
            );
        }),
        destroyAll(): void {
            states.value.clear();
        },
        destroySectionState({ internal_id }): void {
            states.value.delete(internal_id);
        },
    };
};
