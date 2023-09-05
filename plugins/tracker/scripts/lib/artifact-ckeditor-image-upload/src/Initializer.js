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

import { sprintf } from "sprintf-js";
import prettyKibibytes from "pretty-kibibytes";
import {
    buildFileUploadHandler,
    MaxSizeUploadExceededError,
    UploadError,
} from "@tuleap/ckeditor-image-upload";
import { disableFormSubmit, enableFormSubmit } from "./form-adapter";
import { addInstance } from "./consistent-uploaded-files-before-submit-checker";
import { disablePasteOfImages } from "./paste-image-disabler";

export class Initializer {
    constructor(doc, gettext_provider, detector) {
        this.doc = doc;
        this.gettext_provider = gettext_provider;
        this.detector = detector;
    }

    init(ckeditor_instance, textarea) {
        if (!this.detector.isUploadEnabled()) {
            disablePasteOfImages(ckeditor_instance, this.gettext_provider);
            return;
        }

        const form = textarea.form;
        const field_name = textarea.dataset.uploadFieldName;
        const max_size_upload = parseInt(textarea.dataset.uploadMaxSize, 10);

        const onStartCallback = () => disableFormSubmit(form);
        const onErrorCallback = (error) => {
            if (error instanceof MaxSizeUploadExceededError) {
                error.loader.message = sprintf(
                    this.gettext_provider.gettext(
                        "You are not allowed to upload files bigger than %s.",
                    ),
                    prettyKibibytes(error.max_size_upload),
                );
            } else if (error instanceof UploadError) {
                error.loader.message = this.gettext_provider.gettext("Unable to upload the file");
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
}
