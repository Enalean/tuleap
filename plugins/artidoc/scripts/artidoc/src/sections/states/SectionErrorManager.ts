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

import type { Fault } from "@tuleap/fault";
import type { SectionState } from "@/sections/states/SectionStateBuilder";
import { isOutdatedSectionFault } from "@/helpers/CheckSectionConcurrentEdition";

export type ManageErrorState = {
    handleError(fault: Fault): void;
    resetErrorStates(): void;
};

function isNotFound(fault: Fault): boolean {
    return "isNotFound" in fault && fault.isNotFound() === true;
}

function isForbidden(fault: Fault): boolean {
    return "isForbidden" in fault && fault.isForbidden() === true;
}

export const getSectionErrorManager = (section_state: SectionState): ManageErrorState => ({
    handleError(fault: Fault): void {
        if (isOutdatedSectionFault(fault)) {
            section_state.is_outdated.value = true;
            return;
        }

        section_state.is_in_error.value = true;
        if (isNotFound(fault) || isForbidden(fault)) {
            section_state.is_not_found.value = true;
        }
        section_state.error_message.value = String(fault);
    },
    resetErrorStates(): void {
        section_state.is_outdated.value = false;
        section_state.is_in_error.value = false;
        section_state.is_not_found.value = false;
    },
});
