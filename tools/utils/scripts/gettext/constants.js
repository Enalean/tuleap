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

/* From https://github.com/Polyconseil/easygettext/ */

exports.DEFAULT_ATTRIBUTES = ["get-text", "i18n", "translate"];

exports.DEFAULT_FILTERS = ["i18n", "translate"];

exports.DEFAULT_VUE_GETTEXT_FUNCTIONS = {
    $gettext: ["msgid"],
    $ngettext: ["msgid", "plural", null],
    $pgettext: ["msgctxt", "msgid"]
};

exports.DEFAULT_START_DELIMITER = "{{";
exports.DEFAULT_END_DELIMITER = "}}";

// Could for example be '::', used by AngularJS to indicate one-time bindings.
exports.DEFAULT_FILTER_PREFIX = null;

exports.DEFAULT_DELIMITERS = {
    start: "{{",
    end: "}}"
};

exports.ATTRIBUTE_COMMENT = "comment";
exports.ATTRIBUTE_CONTEXT = "context";
exports.ATTRIBUTE_PLURAL = "plural";

exports.MARKER_NO_CONTEXT = "__NOCONTEXT__";
