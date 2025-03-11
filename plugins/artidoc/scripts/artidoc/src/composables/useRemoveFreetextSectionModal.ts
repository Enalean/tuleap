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
import { noop } from "@/helpers/noop";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";

type RemoveFreetextSectionModalHandler = (section: ReactiveStoredArtidocSection) => void;

export interface UseRemoveFreetextSectionModal {
    readonly registerHandler: (new_handler: RemoveFreetextSectionModalHandler) => void;
    readonly openModal: (section: ReactiveStoredArtidocSection) => void;
}

export const REMOVE_FREETEXT_SECTION_MODAL: StrictInjectionKey<UseRemoveFreetextSectionModal> =
    Symbol("open_remove_freetext_section_modal");

export function useRemoveFreetextSectionModal(): UseRemoveFreetextSectionModal {
    let handler: RemoveFreetextSectionModalHandler = noop;

    return {
        registerHandler: (new_handler: RemoveFreetextSectionModalHandler): void => {
            handler = new_handler;
        },
        openModal: (section: ReactiveStoredArtidocSection): void => {
            handler(section);
        },
    };
}
