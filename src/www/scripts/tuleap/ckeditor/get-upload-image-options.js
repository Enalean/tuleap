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

import { sprintf } from "sprintf-js";
import prettyKibibytes from "pretty-kibibytes";
import { isUploadEnabled, informUsersThatTheyCanPasteImagesInEditor } from "./element-adapter.js";
import { addInstance } from "./consistent-uploaded-files-before-submit-checker.js";
import { initGettext } from "../gettext/gettext-factory.js";
import { disableFormSubmit, enableFormSubmit } from "./form-adapter.js";
import {
    buildFileUploadHandler,
    MaxSizeUploadExceededError,
    UploadError,
} from "./file-upload-handler-factory.js";
import { isThereAnImageWithDataURI } from "./image-urls-finder.js";
import { getPOFileFromLocale } from "../gettext/gettext-init";

export async function initiateUploadImage(ckeditor_instance, options, element) {
    const gettext_provider = await initGettext(options.language, "rich-text-editor", (locale) =>
        import(/* webpackChunkName: "rich-text-editor-po-" */ "./po/" + getPOFileFromLocale(locale))
    );
    if (!isUploadEnabled(element)) {
        disablePasteOfImages(ckeditor_instance, gettext_provider);
        return;
    }

    const form = element.form;
    const field_name = element.dataset.uploadFieldName;
    const max_size_upload = parseInt(element.dataset.uploadMaxSize, 10);

    informUsersThatTheyCanPasteImagesInEditor(element);

    const onStartCallback = () => disableFormSubmit(form);
    const onErrorCallback = (error) => {
        if (error instanceof MaxSizeUploadExceededError) {
            error.loader.message = sprintf(
                gettext_provider.gettext("You are not allowed to upload files bigger than %s."),
                prettyKibibytes(error.max_size_upload)
            );
        } else if (error instanceof UploadError) {
            error.loader.message = gettext_provider.gettext("Unable to upload the file");
        }
        enableFormSubmit(form);
    };

    function onSuccessCallback(id, download_href) {
        const hidden_field = document.createElement("input");
        hidden_field.type = "hidden";
        hidden_field.name = field_name;
        hidden_field.value = id;
        hidden_field.dataset.url = download_href;
        form.appendChild(hidden_field);

        enableFormSubmit(form);
    }

    const fileUploadRequestHandler = buildFileUploadHandler({
        ckeditor_instance,
        max_size_upload,
        onStartCallback,
        onErrorCallback,
        onSuccessCallback,
    });

    ckeditor_instance.on("fileUploadRequest", fileUploadRequestHandler, null, null, 4);
    addInstance(form, ckeditor_instance, field_name);
}

function disablePasteOfImages(ckeditor_instance, gettext_provider) {
    ckeditor_instance.on("paste", (event) => {
        if (isThereAnImageWithDataURI(event.data.dataValue)) {
            event.data.dataValue = "";
            event.cancel();
            ckeditor_instance.showNotification(
                gettext_provider.gettext("You are not allowed to paste images here"),
                "warning"
            );
        }
    });
}

export function getUploadImageOptions(element) {
    if (!isUploadEnabled(element)) {
        return {};
    }

    return {
        extraPlugins: "uploadimage",
        uploadUrl: element.dataset.uploadUrl,
    };
}
