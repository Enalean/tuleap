/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import type { RootState } from "./type";
import type { FetchWrapperError } from "@tuleap/tlp-fetch";

export async function setApplicationInErrorStateDueToRestError(
    state: RootState,
    rest_error: FetchWrapperError,
): Promise<void> {
    state.should_display_error_state = true;

    if (rest_error.response.status === 400) {
        try {
            state.error_message = await getMessageFromRestError(rest_error);
        } catch (error) {
            // no custom message if we are unable to parse the error response
            throw rest_error;
        }
    }
}

export function setApplicationInEmptyState(state: RootState): void {
    state.should_display_empty_state = true;
}

async function getMessageFromRestError(rest_error: FetchWrapperError): Promise<string> {
    const response = await rest_error.response.json();

    if (Object.prototype.hasOwnProperty.call(response, "error")) {
        if (Object.prototype.hasOwnProperty.call(response.error, "i18n_error_message")) {
            return response.error.i18n_error_message;
        }

        return response.error.message;
    }

    return "";
}

export function stopLoading(state: RootState): void {
    state.is_loading = false;
}

export function toggleClosedElements(state: RootState, show_closed_elements: boolean): void {
    state.show_closed_elements = show_closed_elements;
}
