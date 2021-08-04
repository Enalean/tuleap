/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
import "select2/dist/js/select2.full";

import default_locale from "../js/default_locale";
import type {
    Options,
    Select2Plugin,
    DataFormat,
    LoadingData,
    IdTextPair,
    GroupedDataFormat,
} from "../js/select2";

export type { Options, Select2Plugin, DataFormat, LoadingData, IdTextPair, GroupedDataFormat };

export function select2(element: Element, options?: Options): Select2Plugin {
    let theme = "tlp-select2";
    if (element && element.classList.contains("tlp-select-small")) {
        theme = "tlp-select2-small";
    } else if (element && element.classList.contains("tlp-select-large")) {
        theme = "tlp-select2-large";
    }

    // jQuery().select2 should yield a Select2Plugin but apparently it doesn't
    // eslint-disable-next-line @typescript-eslint/consistent-type-assertions
    return jQuery(element).select2({
        language: default_locale,
        ...options,
        theme,
    }) as unknown as Select2Plugin;
}
