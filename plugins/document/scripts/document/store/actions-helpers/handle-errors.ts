/**
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

import type { Item, RootState } from "../../type";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import type { Store } from "vuex";
import { getErrorMessage } from "../../helpers/properties-helpers/error-handler-helper";

export async function handleErrors(
    store: {
        commit: Store<RootState>["commit"];
    },
    exception: unknown,
): Promise<void> {
    const message = "Internal server error";
    if (!(exception instanceof FetchWrapperError) || exception.response === undefined) {
        store.commit("error/setFolderLoadingError", message);
        throw exception;
    }

    const status = exception.response.status;
    if (status === 403) {
        store.commit("error/switchFolderPermissionError");
        return;
    }

    try {
        const json = await exception.response.json();
        store.commit("error/setFolderLoadingError", getErrorMessage(json));
    } catch (error) {
        store.commit("error/setFolderLoadingError", message);
    }
}

export async function handleErrorsForDocument(
    store: {
        commit: Store<RootState>["commit"];
    },
    exception: unknown,
): Promise<void> {
    const message = "Internal server error";
    if (!(exception instanceof FetchWrapperError) || exception.response === undefined) {
        store.commit("error/setItemLoadingError", message);
        throw exception;
    }

    const status = exception.response.status;
    if (status === 403) {
        store.commit("error/switchItemPermissionError");
        return;
    }

    try {
        const json = await exception.response.json();
        store.commit("error/setItemLoadingError", getErrorMessage(json));
    } catch (error) {
        store.commit("error/setItemLoadingError", message);
    }
}

export async function handleErrorsForDeletionModal(
    store: {
        commit: Store<RootState>["commit"];
    },
    exception: unknown,
    item: Item,
): Promise<void> {
    const message = "Internal server error";
    if (!(exception instanceof FetchWrapperError) || exception.response === undefined) {
        store.commit("error/setModalError", message);
        throw exception;
    }
    try {
        const json = await exception.response.json();
        store.commit("error/setModalError", getErrorMessage(json));

        if (json.error.code === 404) {
            store.commit("removeItemFromFolderContent", item);
            store.commit("updateCurrentlyPreviewedItem", null);
        }
    } catch (error) {
        store.commit("error/setModalError", message);
    }
}
