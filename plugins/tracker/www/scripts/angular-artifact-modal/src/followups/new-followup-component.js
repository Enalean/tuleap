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

import "./new-followup.html";
import { TEXT_FORMAT_HTML, TEXT_FORMAT_TEXT } from "../../../constants/fields-constants.js";

export default {
    templateUrl: "new-followup.html",
    controller,
    bindings: {
        value: "="
    }
};

function controller() {
    const self = this;

    Object.assign(self, {
        isTextCurrentFormat,
        isHTMLCurrentFormat,
        text_format: TEXT_FORMAT_TEXT,
        html_format: TEXT_FORMAT_HTML,
        ckeditor_config: {
            toolbar: [
                ["Bold", "Italic", "Underline"],
                ["NumberedList", "BulletedList", "-", "Blockquote", "Format"],
                ["Link", "Unlink", "Anchor", "Image"],
                ["Source"]
            ],
            height: "100px"
        }
    });

    function isTextCurrentFormat() {
        return self.value.format === TEXT_FORMAT_TEXT;
    }

    function isHTMLCurrentFormat() {
        return self.value.format === TEXT_FORMAT_HTML;
    }
}
