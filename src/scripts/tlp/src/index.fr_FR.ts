/*
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

import flatpickr from "flatpickr";
import { French } from "flatpickr/dist/l10n/fr.js";

export * from "./js/index";

import "select2/dist/js/i18n/fr.js";

import locale from "./vendor-i18n/fr_FR/tlp.fr";
import type { Options, Select2Plugin } from "./js/index";
import { select2 } from "./js/index";

flatpickr.localize(French);

function frenchSelect2(element: Element, options?: Options): Select2Plugin {
    return select2(element, { language: locale, ...options });
}

export { locale, frenchSelect2 as select2 };
