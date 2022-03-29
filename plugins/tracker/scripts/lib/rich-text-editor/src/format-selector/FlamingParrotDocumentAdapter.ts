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

import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "@tuleap/plugin-tracker-constants";

export const HTML_FORMAT_CLASSNAME = "default_format_html";
export const TEXT_FORMAT_CLASSNAME = "default_format_text";

export class FlamingParrotDocumentAdapter {
    constructor(private readonly doc: Document) {}

    public getDefaultFormat(): TextFieldFormat {
        if (this.doc.body.classList.contains(HTML_FORMAT_CLASSNAME)) {
            return TEXT_FORMAT_HTML;
        }

        if (this.doc.body.classList.contains(TEXT_FORMAT_CLASSNAME)) {
            return TEXT_FORMAT_TEXT;
        }

        return TEXT_FORMAT_COMMONMARK;
    }

    public createAndInsertMountPoint(textarea: HTMLTextAreaElement): HTMLDivElement {
        const mount_point = this.doc.createElement("div");
        textarea.insertAdjacentElement("beforebegin", mount_point);
        return mount_point;
    }
}
