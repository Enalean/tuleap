/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { createPopover } from "@tuleap/tlp-popovers";

export type PopoverHost = {
    button_element: HTMLButtonElement;
    popover_element: HTMLElement;
};

type DisconnectFunction = () => void;

export const connectPopover = (host: PopoverHost): DisconnectFunction => {
    const popover = createPopover(host.button_element, host.popover_element, {
        trigger: "click",
        placement: "bottom-start",
    });

    return () => {
        popover.destroy();
    };
};
