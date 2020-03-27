/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { post } from "tlp-fetch";
import { Upload } from "tus-js-client";

export function buildFileUploadHandler(options) {
    const {
        ckeditor_instance,
        max_size_upload,
        onStartCallback,
        onErrorCallback,
        onSuccessCallback,
    } = options;

    return async function handler(event) {
        const loader = event.data.fileLoader;
        event.stop();

        onStartCallback();

        if (loader.file.size > max_size_upload) {
            onErrorCallback(new MaxSizeUploadExceededError(max_size_upload, loader));
            loader.changeStatus("error");
            return;
        }
        const { id, upload_href, download_href } = await startUpload(loader, onErrorCallback);

        if (!upload_href) {
            onSuccess(loader, download_href);
            onSuccessCallback(id, download_href);
            return;
        }

        try {
            await startUploader(loader, upload_href, download_href);
        } catch (error) {
            onErrorCallback(new UploadError(loader));
            throw error;
        }
        ckeditor_instance.fire("change");
        onSuccessCallback(id, download_href);
    };
}

async function startUpload(loader, onErrorCallback) {
    try {
        const response = await post(loader.uploadUrl, {
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                name: loader.fileName,
                file_size: loader.file.size,
                file_type: loader.file.type,
            }),
        });
        return response.json();
    } catch (exception) {
        onErrorCallback(new UploadError(loader));
        if (typeof exception.response === "undefined") {
            loader.changeStatus("error");
            throw exception;
        }

        try {
            await handleException(loader, exception);
        } finally {
            loader.changeStatus("error");
        }
        throw exception;
    }
}

async function handleException(loader, exception) {
    const json = await exception.response.json();
    if (Object.prototype.hasOwnProperty.call(json, "error")) {
        loader.message = json.error.message;

        if (Object.prototype.hasOwnProperty.call(json.error, "i18n_error_message")) {
            loader.message = json.error.i18n_error_message;
        }
    }
}

function startUploader(loader, upload_href, download_href) {
    return new Promise((resolve, reject) => {
        const uploader = new Upload(loader.file, {
            uploadUrl: upload_href,
            retryDelays: [0, 1000, 3000, 5000],
            metadata: {
                filename: loader.file.name,
                filetype: loader.file.type,
            },
            onProgress: (bytes_sent, bytes_total) => {
                loader.uploadTotal = bytes_total;
                loader.uploaded = bytes_sent;
                loader.update();
            },
            onSuccess: () => {
                onSuccess(loader, download_href);
                return resolve();
            },
            onError: (error) => {
                onError(loader, error.originalRequest);
                return reject(error);
            },
        });

        uploader.start();
    });
}

function onError(loader, originalRequest) {
    loader.message = loader.lang.filetools["httpError" + originalRequest.status];
    if (!loader.message) {
        loader.message = loader.lang.filetools.httpError.replace("%1", originalRequest.status);
    }
    loader.changeStatus("error");
}

function onSuccess(loader, download_href) {
    loader.responseData = {
        // ckeditor uploadImage widget inserts real size of the image as inline style
        // which causes strange rendering for big images in the artifact view once
        // the artifact is updated.
        // Using blank width & height inhibits this behavior.
        // See https://github.com/ckeditor/ckeditor-dev/blob/4.11.1/plugins/uploadimage/plugin.js#L84-L86
        width: " ",
        height: " ",
    };
    loader.uploaded = 1;
    loader.fileName = loader.file.name;
    loader.url = download_href;
    loader.changeStatus("uploaded");
}

export class MaxSizeUploadExceededError extends Error {
    constructor(max_size_upload, loader) {
        super();
        this.name = "MaxSizeUploadExceededError";
        this.loader = loader;
        this.max_size_upload = max_size_upload;
    }
}

export class UploadError extends Error {
    constructor(loader) {
        super();
        this.name = "UploadError";
        this.loader = loader;
    }
}
