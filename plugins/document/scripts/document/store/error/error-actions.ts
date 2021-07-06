/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import type { ActionContext } from "vuex";
import type { RootState, State } from "../../type";
import type { FetchWrapperError } from "@tuleap/tlp-fetch";
import type { DocumentException } from "../actions-helpers/handle-errors";
import { getErrorMessage } from "../actions-helpers/handle-errors";
import type { ErrorState } from "./module";

export async function handleGlobalModalError(
    context: ActionContext<State, State>,
    rest_error: FetchWrapperError
): Promise<void> {
    try {
        const { error } = await rest_error.response.json();
        context.commit("setGlobalModalErrorMessage", error.code + " " + error.message);
    } catch (e) {
        context.commit("setGlobalModalErrorMessage", "");
    }
}

export async function handleErrorsForModal(
    context: ActionContext<ErrorState, RootState>,
    exception: DocumentException
): Promise<void> {
    const message = "Internal server error";
    if (exception.response === undefined) {
        context.commit("error/setModalError", message);
        throw exception;
    }
    try {
        const json = await exception.response.json();
        context.commit("error/setModalError", getErrorMessage(json));
    } catch (error) {
        context.commit("error/setModalError", message);
    }
}
