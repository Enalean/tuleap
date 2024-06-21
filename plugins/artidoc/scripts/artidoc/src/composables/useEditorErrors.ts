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
import type { Fault } from "@tuleap/fault";
import { isOutdatedSectionFault } from "@/helpers/get-section-in-its-latest-version";
import type { Ref } from "vue";
import { ref } from "vue";

export type EditorErrors = {
    is_in_error: Ref<boolean>;
    is_outdated: Ref<boolean>;
    is_not_found: Ref<boolean>;
    getErrorMessage: () => string;
    resetErrorStates: () => void;
    handleError: (fault: Fault) => void;
};

export function useEditorErrors(): EditorErrors {
    const is_not_found = ref(false);
    const error_message = ref("");
    const is_outdated = ref(false);
    const is_in_error = ref(false);

    function handleError(fault: Fault): void {
        if (isOutdatedSectionFault(fault)) {
            is_outdated.value = true;
            return;
        }

        is_in_error.value = true;
        if (isNotFound(fault) || isForbidden(fault)) {
            is_not_found.value = true;
        }
        error_message.value = String(fault);
    }

    function resetErrorStates(): void {
        is_outdated.value = false;
        is_in_error.value = false;
        is_not_found.value = false;
    }

    function isNotFound(fault: Fault): boolean {
        return "isNotFound" in fault && fault.isNotFound() === true;
    }

    function isForbidden(fault: Fault): boolean {
        return "isForbidden" in fault && fault.isForbidden() === true;
    }

    return {
        is_in_error: is_in_error,
        is_outdated: is_outdated,
        is_not_found: is_not_found,
        getErrorMessage: () => error_message.value,
        resetErrorStates: resetErrorStates,
        handleError: handleError,
    };
}
