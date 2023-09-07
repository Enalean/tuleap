/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
import type { Placement } from "@floating-ui/dom";
export type { Placement };
import type { Popover, PopoverOptions } from "./popovers";
export type { Popover, PopoverOptions };
import { createPopover as createPopoverImplementation } from "./popovers";
export { EVENT_TLP_POPOVER_SHOWN, EVENT_TLP_POPOVER_HIDDEN } from "./popovers";

// Apply partially the popover creation function to pass document
export const createPopover = (
    popover_trigger: HTMLElement,
    popover_content: HTMLElement,
    options?: PopoverOptions,
): Popover => createPopoverImplementation(document, popover_trigger, popover_content, options);
