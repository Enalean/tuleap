/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
import type { ErrorState } from "./module";

export function resetErrors(state: ErrorState): void {
    state.has_folder_permission_error = false;
    state.has_folder_loading_error = false;
    state.folder_loading_error = null;
    state.has_document_permission_error = false;
    state.has_document_loading_error = false;
    state.document_loading_error = null;
    state.has_document_lock_error = false;
    state.document_lock_error = null;
    state.has_global_modal_error = false;
    state.global_modal_error_message = null;
}

export function switchFolderPermissionError(state: ErrorState): void {
    state.has_folder_permission_error = true;
}

export function switchItemPermissionError(state: ErrorState): void {
    state.has_document_permission_error = true;
}

export function setFolderLoadingError(state: ErrorState, message: string): void {
    state.has_folder_loading_error = true;
    state.folder_loading_error = message;
}

export function setItemLoadingError(state: ErrorState, message: string): void {
    state.has_document_loading_error = true;
    state.document_loading_error = message;
}

export function setModalError(state: ErrorState, error_message: string): void {
    state.has_modal_error = true;
    state.modal_error = error_message;
}

export function resetModalError(state: ErrorState): void {
    state.has_modal_error = false;
    state.modal_error = null;
}

export function setLockError(state: ErrorState, error_message: string): void {
    state.has_document_lock_error = true;
    state.document_lock_error = error_message;
}

export function setGlobalModalErrorMessage(state: ErrorState, message: string): void {
    state.has_global_modal_error = true;
    state.global_modal_error_message = message;
}
