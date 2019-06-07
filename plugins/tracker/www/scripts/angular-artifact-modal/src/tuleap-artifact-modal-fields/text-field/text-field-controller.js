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

import { TEXT_FORMAT_HTML, TEXT_FORMAT_TEXT } from "../../../../constants/fields-constants.js";

export default function TextFieldController() {
    const self = this;

    Object.assign(self, {
        $onInit: init,
        isRequiredAndEmpty,
        isTextCurrentFormat,
        isHTMLCurrentFormat,
        text_format: TEXT_FORMAT_TEXT,
        html_format: TEXT_FORMAT_HTML,
        ckeditor_config: null
    });

    function init() {
        self.ckeditor_config = {
            toolbar: [
                ["Bold", "Italic", "Underline"],
                ["NumberedList", "BulletedList", "-", "Blockquote", "Format"],
                ["Link", "Unlink", "Anchor", "Image"],
                ["Source"]
            ],
            height: "100px",
            readOnly: self.isDisabled()
        };
    }

    function isTextCurrentFormat() {
        return self.value_model.value.format === TEXT_FORMAT_TEXT;
    }

    function isHTMLCurrentFormat() {
        return self.value_model.value.format === TEXT_FORMAT_HTML;
    }

    function isRequiredAndEmpty() {
        return self.field.required && self.value_model.value.content === "";
    }
}
