/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import "../themes/style.scss";
import type { Dropdown, DropdownOptions } from "./dropdowns";
import { createDropdown as createDropdownImplementation } from "./dropdowns";

export { EVENT_TLP_DROPDOWN_HIDDEN, EVENT_TLP_DROPDOWN_SHOWN } from "./dropdowns";
export type { Dropdown, DropdownOptions };
// Apply partially the dropdowns creation function to pass document
export const createDropdown = (trigger: Element, options?: DropdownOptions): Dropdown =>
    createDropdownImplementation(document, trigger, options);
