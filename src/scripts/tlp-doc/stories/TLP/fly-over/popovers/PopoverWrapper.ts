/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { createPopover, type Popover } from "@tuleap/tlp-popovers";

type PositionRelativeToAnchor = "top" | "bottom" | "left" | "right";
type PositionOfArrow = "start" | "end";
type PopoverPlacement = PositionRelativeToAnchor | `${PositionRelativeToAnchor}-${PositionOfArrow}`;

class PopoverWrapper extends HTMLElement {
    popover_instance: Popover | undefined = undefined;
    #placement: PopoverPlacement = "bottom";
    #trigger: "click" | "hover" = "hover";
    #anchor: boolean = false;
    set placement(placement: PopoverPlacement) {
        this.#placement = placement;
        this.update();
    }
    set trigger(trigger: "click" | "hover") {
        this.#trigger = trigger;
        this.update();
    }
    set anchor(anchor: boolean) {
        this.#anchor = anchor;
        this.update();
    }

    update(): void {
        this.popover_instance?.destroy();
        const anchor_element: HTMLElement | null = this.querySelector("#popover-anchor-example");
        const trigger_element: HTMLElement | null = this.#anchor
            ? this.querySelector("#popover-anchor-example-trigger")
            : this.querySelector("#popover-example");
        const pop: HTMLElement | null = this.querySelector("#popover-example-content");
        if (!pop || !trigger_element) {
            return;
        }
        if (this.#anchor && anchor_element) {
            this.popover_instance = createPopover(trigger_element, pop, {
                anchor: anchor_element,
            });
        } else {
            this.popover_instance = createPopover(trigger_element, pop, {
                placement: this.#placement,
                trigger: this.#trigger,
            });
        }
    }

    connectedCallback(): void {
        this.update();
    }
}

if (!window.customElements.get("tuleap-popover-wrapper")) {
    window.customElements.define("tuleap-popover-wrapper", PopoverWrapper);
}
