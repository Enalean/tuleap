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

import "../styles/main.scss";
import type flatpickr from "flatpickr";
import type { LocaleString } from "@tuleap/locale";
import { fr_FR_LOCALE, en_US_LOCALE } from "@tuleap/locale";
import { French } from "flatpickr/dist/l10n/fr";
import type { DatePickerInstance } from "./flatpickr";
import { datePicker as flatpickrDatePicker } from "./flatpickr";

export { getLocaleWithDefault } from "@tuleap/locale";
export type { DatePickerInstance };

export function createDatePicker(
    element: HTMLInputElement,
    locale: LocaleString,
    options?: flatpickr.Options.Options,
): DatePickerInstance {
    const defaulted_options = options ?? {};
    if (locale === fr_FR_LOCALE) {
        defaulted_options.locale = French;
    }
    return flatpickrDatePicker(element, defaulted_options);
}

/** @deprecated replaced by createDatePicker */
export function datePicker(
    element: HTMLInputElement,
    options?: flatpickr.Options.Options,
): DatePickerInstance {
    return createDatePicker(element, en_US_LOCALE, options);
}
