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

import { UploadEnabledDetector } from "./UploadEnabledDetector";

function createDocument() {
    return document.implementation.createHTMLDocument();
}

describe(`UploadEnabledDetector`, () => {
    let doc, detector, element;

    beforeEach(() => {
        doc = createDocument();
        element = doc.createElement("textarea");
        doc.body.append(element);
        detector = new UploadEnabledDetector(doc, element);
    });

    describe(`isUploadEnabled`, () => {
        it(`when the attribute "data-upload-is-enabled" can't be found in the document
            (when there is no file field), it returns false`, () => {
            expect(detector.isUploadEnabled()).toBe(false);
        });

        it(`when the given element has no "data-upload-url" attribute, it returns false`, () => {
            const file_field = doc.createElement("div");
            file_field.dataset.uploadIsEnabled = "true";
            doc.body.append(file_field);

            expect(detector.isUploadEnabled()).toBe(false);
        });

        it(`when both data-attributes can be found, it returns true`, () => {
            const file_field = doc.createElement("div");
            file_field.dataset.uploadIsEnabled = "true";
            doc.body.append(file_field);
            element.dataset.uploadUrl = "https://example.com/upload";

            expect(detector.isUploadEnabled()).toBe(true);
        });
    });
});
