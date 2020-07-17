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

import { Modal, ModalOptions } from "./js/modal";
import { Popover } from "./js/popovers";
import flatpickr from "flatpickr";
import { Options } from "flatpickr/dist/types/options";
import { Dropdown, DropdownOptions } from "./js/dropdowns";
import { PopperOptions } from "popper.js";

export * from "./js/fetch-wrapper";
export * from "./js/select2";

export { Modal, ModalOptions };
export function modal(element: Element, options?: ModalOptions): Modal;
export { Dropdown, DropdownOptions };
export function dropdown(trigger: Element, options?: DropdownOptions): Dropdown;
export { PopperOptions, Popover };
export function createPopover(
    popover_trigger: Element,
    popover_content: Element,
    options?: PopperOptions & { anchor?: Element; trigger?: "click" | "hover" }
): Popover;

export function datePicker(
    element: Element,
    options?: Omit<Options, "enableTime" | "dateFormat"> & {
        weekNumbers?: true;
        time_24hr?: true;
        monthSelectorType?: "static";
    }
): flatpickr.Instance;

export as namespace tlp;
