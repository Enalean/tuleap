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

import { getUploadImageOptions } from "./ckeditor-options.js";

function createDocument() {
    return document.implementation.createHTMLDocument();
}

describe(`ckeditor-options`, () => {
    let doc;
    beforeEach(() => {
        doc = createDocument();
    });

    describe(`getUploadImageOptions()`, () => {
        let element;

        it(`when upload is disabled, it returns an empty object`, () => {
            expect(getUploadImageOptions(element)).toEqual({});
        });

        it(`when upload is enabled, it returns CKEditor options`, () => {
            const file_field = document.createElement("div");
            file_field.dataset.uploadIsEnabled = "true";
            document.body.append(file_field);
            element = doc.createElement("div");
            element.dataset.uploadUrl = "https://example.com/disprobabilize/gavyuti";

            const result = getUploadImageOptions(element);

            expect(result).toEqual({
                extraPlugins: "uploadimage",
                uploadUrl: "https://example.com/disprobabilize/gavyuti",
                clipboard_handleImages: false,
            });
            file_field.remove();
        });
    });
});
