/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

interface CKEditorFileLoader {
    message: string;
}

export class MaxSizeUploadExceededError extends Error {
    name: "MaxSizeUploadExceededError";
    loader: CKEditorFileLoader;
    max_size_upload: number;
    constructor(max_size_upload: number, loader: CKEditorFileLoader);
}

export class UploadError extends Error {
    name: "UploadError";
    loader: CKEditorFileLoader;
    constructor(loader: CKEditorFileLoader);
}

type StartCallback = () => void;
type ErrorCallback = (error: UploadError | MaxSizeUploadExceededError) => void;
type SuccessCallback = (id: number, download_href: string) => void;

interface FactoryOptions {
    ckeditor_instance: CKEDITOR.editor;
    max_size_upload: number;
    onStartCallback: StartCallback;
    onErrorCallback: ErrorCallback;
    onSuccessCallback: SuccessCallback;
}

type UploadHandler = (event: CKEDITOR.eventInfo) => Promise<void>;

export function buildFileUploadHandler(options: FactoryOptions): UploadHandler;
