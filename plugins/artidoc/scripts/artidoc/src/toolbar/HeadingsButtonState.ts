/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
import { ref } from "vue";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";

export type HeadingsButtonState = {
    readonly active_section: Ref<ReactiveStoredArtidocSection | undefined>;
    readonly is_button_active: Ref<boolean>;
    activateButtonForSection(section: ReactiveStoredArtidocSection): void;
    deactivateButton(): void;
};

export const getHeadingsButtonState = (): HeadingsButtonState => {
    const active_section: Ref<ReactiveStoredArtidocSection | undefined> = ref(undefined);
    const is_button_active = ref(false);

    return {
        active_section,
        is_button_active,
        activateButtonForSection(section: ReactiveStoredArtidocSection): void {
            is_button_active.value = true;
            active_section.value = section;
        },
        deactivateButton(): void {
            is_button_active.value = false;
            active_section.value = undefined;
        },
    };
};
