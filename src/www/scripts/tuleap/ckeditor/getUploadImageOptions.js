/**
 * Copyright (c) Enalean, 2019 - Present. All rights reserved
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
import "css.escape";
import Gettext from "node-gettext";

let gettext_provider;

async function getGettextProvider(options) {
    if (typeof gettext_provider !== "undefined") {
        return gettext_provider;
    }

    gettext_provider = new Gettext();
    if (options.language === "fr_FR") {
        try {
            const french_translations = await import(/* webpackChunkName: "rich-text-editor-fr" */ "./po/fr.po");
            gettext_provider.addTranslations(
                options.language,
                "rich-text-editor",
                french_translations
            );
        } catch (exception) {
            // will be en_US if translations cannot be loaded
        }
    }

    gettext_provider.setLocale(options.language);
    gettext_provider.setTextDomain("rich-text-editor");

    return gettext_provider;
}

function initiateUploadImage(ckeditor_instance, options, form, field_name) {
    if (typeof options.uploadUrl === "undefined") {
        return;
    }

    ckeditor_instance.on("fileUploadRequest", fileUploadRequest, null, null, 4);
    form.addEventListener("submit", removeUnusedUploadedFilesFromForm);

    async function fileUploadRequest(evt) {
        const loader = evt.data.fileLoader;
        evt.stop();

        try {
            const response = await post(loader.uploadUrl, {
                headers: { "content-type": "application/json" },
                body: JSON.stringify({
                    name: loader.fileName,
                    file_size: loader.file.size,
                    file_type: loader.file.type
                })
            });

            const { id, upload_href, download_href } = await response.json();

            if (!upload_href) {
                onSuccess(loader, id, download_href);
                return;
            }

            const uploader = new Upload(loader.file, {
                uploadUrl: upload_href,
                retryDelays: [0, 1000, 3000, 5000],
                metadata: {
                    filename: loader.file.name,
                    filetype: loader.file.type
                },
                onSuccess: () => {
                    onSuccess(loader, id, download_href);
                },
                onError: ({ originalRequest }) => {
                    onError(loader, originalRequest);
                }
            });

            uploader.start();
        } catch (exception) {
            loader.message = (await getGettextProvider(options)).gettext(
                "Unable to upload the file"
            );
            if (typeof exception.response === "undefined") {
                loader.changeStatus("error");
                return;
            }

            try {
                const json = await exception.response.json();
                if (json.hasOwnProperty("error")) {
                    loader.message = json.error.message;

                    if (json.error.hasOwnProperty("i18n_error_message")) {
                        loader.message = json.error.i18n_error_message;
                    }
                }
            } finally {
                loader.changeStatus("error");
            }
        }
    }

    function onSuccess(loader, id, download_href) {
        loader.responseData = {};
        loader.uploaded = 1;
        loader.fileName = loader.file.name;
        loader.url = download_href;
        loader.changeStatus("uploaded");

        const hidden_field = document.createElement("input");
        hidden_field.type = "hidden";
        hidden_field.name = field_name;
        hidden_field.value = id;
        hidden_field.dataset.url = download_href;
        form.appendChild(hidden_field);

        ckeditor_instance.fire("change");
    }

    function onError(loader, originalRequest) {
        loader.message = loader.lang.filetools["httpError" + originalRequest.status];
        if (!loader.message) {
            loader.message = loader.lang.filetools.httpError.replace("%1", originalRequest.status);
        }
        loader.changeStatus("error");
    }

    function removeUnusedUploadedFilesFromForm() {
        const used_urls = Array.from(
            new DOMParser()
                .parseFromString(ckeditor_instance.getData(), "text/html")
                .querySelectorAll("img")
        ).map(img => img.getAttribute("src"));

        const selector = "input[type=hidden][name=" + CSS.escape(field_name) + "]";
        const potentially_used_uploaded_files = form.querySelectorAll(selector);
        for (const input of potentially_used_uploaded_files) {
            if (used_urls.find(used_url => used_url === input.dataset.url)) {
                continue;
            }

            input.parentNode.removeChild(input);
        }
    }
}

function getUploadImageOptions(element) {
    const options = {};
    const upload_url = element.dataset.uploadUrl;

    if (!upload_url) {
        return options;
    }

    return {
        extraPlugins: "uploadimage",
        uploadUrl: upload_url
    };
}

export { getUploadImageOptions, initiateUploadImage };
