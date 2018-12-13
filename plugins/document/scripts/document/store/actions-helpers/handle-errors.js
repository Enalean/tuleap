/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

export async function handleErrors(context, exception) {
    const status = exception.response.status;
    if (status === 403) {
        context.commit("switchFolderPermissionError");
        return;
    }

    const json = await exception.response.json();
    context.commit("setFolderLoadingError", getErrorMessage(json));
}

function getErrorMessage(error_json) {
    if (error_json.hasOwnProperty("error")) {
        if (error_json.error.hasOwnProperty("i18n_error_message")) {
            return error_json.error.i18n_error_message;
        }

        return error_json.error.message;
    }

    return "";
}
