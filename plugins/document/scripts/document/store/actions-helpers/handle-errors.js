/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
    const message = "Internal server error";
    if (exception.response === undefined) {
        context.commit("error/setFolderLoadingError", message);
        throw exception;
    }

    const status = exception.response.status;
    if (status === 403) {
        context.commit("error/switchFolderPermissionError");
        return;
    }

    try {
        const json = await exception.response.json();
        context.commit("error/setFolderLoadingError", getErrorMessage(json));
    } catch (error) {
        context.commit("error/setFolderLoadingError", message);
    }
}

export async function handleErrorsForLock(context, exception) {
    try {
        const json = await exception.response.json();
        context.commit("error/setLockError", getErrorMessage(json));
    } catch (error) {
        context.commit("error/setLockError", "Internal server error");
        throw exception;
    }
}

export async function handleErrorsForDocument(context, exception) {
    const message = "Internal server error";
    if (exception.response === undefined) {
        context.commit("error/setItemLoadingError", message);
        throw exception;
    }

    const status = exception.response.status;
    if (status === 403) {
        context.commit("error/switchItemPermissionError");
        return;
    }

    try {
        const json = await exception.response.json();
        context.commit("error/setItemLoadingError", getErrorMessage(json));
    } catch (error) {
        context.commit("error/setItemLoadingError", message);
    }
}

export async function handleErrorsForModal(context, exception) {
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

export async function handleErrorsForDeletionModal(context, exception, item) {
    const message = "Internal server error";
    if (exception.response === undefined) {
        context.commit("error/setModalError", message);
        throw exception;
    }
    try {
        const json = await exception.response.json();
        context.commit("error/setModalError", getErrorMessage(json));

        if (json.error.code === 404) {
            context.commit("removeItemFromFolderContent", item);
            context.commit("updateCurrentlyPreviewedItem", null);
        }
    } catch (error) {
        context.commit("error/setModalError", message);
    }
}

export function getErrorMessage(error_json) {
    if (Object.prototype.hasOwnProperty.call(error_json, "error")) {
        if (Object.prototype.hasOwnProperty.call(error_json.error, "i18n_error_message")) {
            return error_json.error.i18n_error_message;
        }

        return error_json.error.message;
    }

    return "";
}
