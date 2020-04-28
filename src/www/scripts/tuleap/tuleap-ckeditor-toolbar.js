/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

/* global module:readonly */

const config = {
    toolbar: [
        ["Bold", "Italic"],
        ["NumberedList", "BulletedList", "-", "Blockquote", "Styles", "Format"],
        ["Link", "Unlink", "Anchor", "Image"],
        ["Source"],
    ],
    stylesSet: [
        { name: "Bold", element: "strong", overrides: "b" },
        { name: "Italic", element: "em", overrides: "i" },
        { name: "Code", element: "code" },
        { name: "Subscript", element: "sub" },
        { name: "Superscript", element: "sup" },
    ],
    disableNativeSpellChecker: false,
};

if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    module.exports = {
        config: config,
    };
} else {
    var tuleap = tuleap || {};
    tuleap.ckeditor = tuleap.ckeditor || {};
    tuleap.ckeditor.config = config;
}
