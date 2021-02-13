/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import french_translations from "../po/fr_FR.po";
import type { ListPicker, ListPickerOptions } from "./type";
import * as creator from "./list-picker";
import { initGettextSync } from "@tuleap/gettext";
import "../themes/style.scss";

export type { ListPicker, ListPickerOptions };
export function createListPicker(
    source_select_box: HTMLSelectElement,
    options: ListPickerOptions
): Promise<ListPicker> {
    const gettext_provider = initGettextSync(
        "tuleap-list-picker",
        french_translations,
        options.locale
    );
    return creator.createListPicker(source_select_box, gettext_provider, options);
}
