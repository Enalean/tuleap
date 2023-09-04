/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import type { RootState } from "../type";
import type { FetchWrapperError } from "@tuleap/tlp-fetch";
import type { ActionContext } from "vuex";
import type { ErrorState } from "./type";

export async function handleGlobalError(
    context: ActionContext<ErrorState, RootState>,
    rest_error: FetchWrapperError,
): Promise<void> {
    try {
        const { error } = await rest_error.response.json();
        let message = error.code + " " + error.message;
        if (error.i18n_error_message) {
            message += ": " + error.i18n_error_message;
        }
        context.commit("setGlobalErrorMessage", message);
    } catch (error) {
        context.commit("setGlobalErrorMessage", "");
    }
}

export async function handleModalError(
    context: ActionContext<ErrorState, RootState>,
    rest_error: FetchWrapperError,
): Promise<void> {
    try {
        const { error } = await rest_error.response.json();
        let message = error.code + " " + error.message;
        if (error.i18n_error_message) {
            message += ": " + error.i18n_error_message;
        }
        context.commit("setModalErrorMessage", message);
    } catch (e) {
        context.commit("setModalErrorMessage", "");
    }
}
