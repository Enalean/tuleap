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

import {
    FlamingParrotDocumentAdapter,
    HTML_FORMAT_CLASSNAME,
} from "./FlamingParrotDocumentAdapter";
import { TEXT_FORMAT_COMMONMARK, TEXT_FORMAT_HTML } from "@tuleap/plugin-tracker-constants";

describe(`FlamingParrotDocumentAdapter`, () => {
    let doc: Document, adapter: FlamingParrotDocumentAdapter;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        adapter = new FlamingParrotDocumentAdapter(doc);
    });

    describe(`getDefaultFormat()`, () => {
        it(`when the body has a special CSS class, it returns "html"`, () => {
            doc.body.classList.add(HTML_FORMAT_CLASSNAME);
            expect(adapter.getDefaultFormat()).toEqual(TEXT_FORMAT_HTML);
        });

        it(`when the body does not have the class, it returns "commonmark"`, () => {
            expect(adapter.getDefaultFormat()).toEqual(TEXT_FORMAT_COMMONMARK);
        });
    });

    describe(`createAndInsertMountPoint()`, () => {
        it(`given a textarea,
            it creates an HTML div element to mount the editor area components into
            and inserts it before the textarea`, () => {
            const textarea = doc.createElement("textarea");
            doc.body.append(textarea);
            const mount_point = adapter.createAndInsertMountPoint(textarea);
            expect(mount_point.nextElementSibling).toBe(textarea);
        });
    });
});
