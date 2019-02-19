/**
 * Copyright (c) Enalean, 2018 - 2019. All Rights Reserved.
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

import Popper from "popper.js";
import { findClosestElement } from "./dom-walker.js";

const CLASS_TLP_POPOVER_SHOWN = "tlp-popover-shown";

export default function createPopover(popover_trigger, popover_content, options = {}) {
    listenTriggerEvents(popover_trigger, popover_content, options);

    const anchor = options.anchor || popover_trigger;

    return new Popper(anchor, popover_content, getPopperOptions(anchor, options));
}

function getPopperOptions(anchor, options) {
    const placement = options.placement || anchor.dataset.placement || "bottom";

    return {
        placement,
        modifiers: {
            arrow: {
                element: ".tlp-popover-arrow"
            },
            computeStyle: {
                gpuAcceleration: false
            }
        }
    };
}

function listenTriggerEvents(popover_trigger, popover_content, options) {
    const trigger = options.trigger || popover_trigger.dataset.trigger || "hover";

    if (trigger === "hover") {
        listenHoverEvents(popover_trigger, popover_content);
    } else if (trigger === "click") {
        listenClickEvents(popover_trigger, popover_content);
    }
}

function listenHoverEvents(popover_trigger, popover_content) {
    popover_trigger.addEventListener("mouseover", () => {
        hideAllShownPopovers();
        popover_content.classList.add(CLASS_TLP_POPOVER_SHOWN);
    });
    popover_trigger.addEventListener("mouseout", () => {
        hideAllShownPopovers();
        popover_content.classList.remove(CLASS_TLP_POPOVER_SHOWN);
    });
}

function listenClickEvents(popover_trigger, popover_content) {
    popover_trigger.addEventListener("click", () => {
        const is_shown = popover_content.classList.contains(CLASS_TLP_POPOVER_SHOWN);
        hideAllShownPopovers();
        if (!is_shown) {
            popover_content.classList.add(CLASS_TLP_POPOVER_SHOWN);
        }
    });
    document.addEventListener("click", event => {
        if (
            popover_content.classList.contains(CLASS_TLP_POPOVER_SHOWN) &&
            !findClosestElement(event.target, popover_content) &&
            !findClosestElement(event.target, popover_trigger)
        ) {
            hideAllShownPopovers();
        }
    });
    for (const dismiss of popover_content.querySelectorAll('[data-dismiss="popover"]')) {
        dismiss.addEventListener("click", hideAllShownPopovers);
    }
}

function hideAllShownPopovers() {
    for (const popover of document.querySelectorAll("." + CLASS_TLP_POPOVER_SHOWN)) {
        popover.classList.remove(CLASS_TLP_POPOVER_SHOWN);
    }
}
