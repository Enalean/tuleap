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

import type { HeadingsButton } from "@/toolbar/HeadingsButton";
import type { HeadingsButtonState } from "@/toolbar/HeadingsButtonState";
import type { SectionsStatesCollection } from "@/sections/states/SectionsStatesCollection";
import type { NumberSections } from "@/sections/levels/SectionsNumberer";
import { isUpdateSectionLevelEvent } from "@/toolbar/HeadingsButton";

export type HandleUpdateSectionLevelEvent = {
    handle(event: Event): void;
};

export const getUpdateSectionLevelEventHandler = (
    headings_button: HeadingsButton,
    headings_button_state: HeadingsButtonState,
    states_collection: SectionsStatesCollection,
    sections_numberer: NumberSections,
): HandleUpdateSectionLevelEvent => ({
    handle: (event: Event): void => {
        if (!isUpdateSectionLevelEvent(event)) {
            return;
        }

        const level = event.detail.level;
        const section = headings_button_state.active_section.value;
        if (section === undefined || section.value.level === level) {
            return;
        }

        const section_state = states_collection.getSectionState(section.value);

        section_state.has_title_level_been_changed.value =
            section_state.initial_level.value !== level;
        sections_numberer.setSectionLevel(section, level);

        headings_button.section = section.value;
    },
});
