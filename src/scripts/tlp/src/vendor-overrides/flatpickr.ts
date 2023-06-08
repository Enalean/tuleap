/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
export type DatePickerInstance = flatpickr.Instance;

flatpickr.defaultConfig.prevArrow = "<i class='fa fa-angle-left'></i>";
flatpickr.defaultConfig.nextArrow = "<i class='fa fa-angle-right'></i>";
flatpickr.l10ns.default.firstDayOfWeek = 1;

export function datePicker(
    element: HTMLInputElement,
    options?: flatpickr.Options.Options
): DatePickerInstance {
    if (isNaN(Date.parse(element.value))) {
        element.value = "";
    }
    options = options || {};

    options.weekNumbers = true;
    options.dateFormat = "Y-m-d";
    options.time_24hr = true;
    options.monthSelectorType = "static";

    let placeholder = "yyyy-mm-dd";
    if (element.hasAttribute("data-enabletime")) {
        options.enableTime = true;
        options.dateFormat = "Y-m-d H:i";
        placeholder += " HH:mm";
    }
    element.setAttribute("placeholder", placeholder);

    return flatpickr(element, options);
}
