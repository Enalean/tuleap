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
import type { PositionForSection } from "@/sections/save/SectionsPositionsForSaveRetriever";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { noop } from "@/helpers/noop";

type OpenAddExistingSectionModalHandler = (
    position: PositionForSection,
    on_successful_addition: (section: ArtidocSection) => void,
) => void;

export interface OpenAddExistingSectionModalBus {
    readonly registerHandler: (new_handler: OpenAddExistingSectionModalHandler) => void;
    readonly openModal: (
        position: PositionForSection,
        on_successful_addition: (section: ArtidocSection) => void,
    ) => void;
}

export const OPEN_ADD_EXISTING_SECTION_MODAL_BUS: StrictInjectionKey<OpenAddExistingSectionModalBus> =
    Symbol("open_add_existing_section_modal_bus");

export function useOpenAddExistingSectionModalBus(): OpenAddExistingSectionModalBus {
    let handler: OpenAddExistingSectionModalHandler = noop;

    return {
        registerHandler: (new_handler: OpenAddExistingSectionModalHandler): void => {
            handler = new_handler;
        },
        openModal: (
            position: PositionForSection,
            on_successful_addition: (section: ArtidocSection) => void,
        ): void => {
            handler(position, on_successful_addition);
        },
    };
}
