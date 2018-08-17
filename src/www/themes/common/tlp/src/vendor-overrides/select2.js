/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

import jQuery from "jquery";
import "select2";

import default_locale from "../js/default_locale.js";

export default function overrideSelect2(element, options) {
    options = options || {};

    options.language = options.language || default_locale;

    options.theme = "tlp-select2";

    if (element && element.classList.contains("tlp-select-small")) {
        options.theme = "tlp-select2-small";
    } else if (element && element.classList.contains("tlp-select-large")) {
        options.theme = "tlp-select2-large";
    }

    return jQuery(element).select2(options);
}
