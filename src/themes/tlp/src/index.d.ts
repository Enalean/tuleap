/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

export * from "./js/fetch-wrapper";
export * from "./js/modal";
export * from "./js/dropdowns";
export * from "./js/popovers";
export * from "./js/select2";

import flatpickr from "flatpickr";
import { Options } from "flatpickr/dist/types/options";
export function datePicker(
    element: Element,
    options?: Omit<Options, "enableTime" | "dateFormat"> & {
        weekNumbers?: true;
        time_24hr?: true;
        monthSelectorType?: "static";
    }
): flatpickr.Instance;

export as namespace tlp;
