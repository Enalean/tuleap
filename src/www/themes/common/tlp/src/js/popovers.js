/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
    const anchor = options.anchor || popover_trigger;
    const popper = new Popper(anchor, popover_content, getPopperOptions(anchor, options));

    const dismiss_buttons = popover_content.querySelectorAll('[data-dismiss="popover"]');
    const listeners = buildListeners(
        popover_trigger,
        popover_content,
        dismiss_buttons,
        options,
        popper
    );
    attachListeners(listeners);

    const destroy = () => {
        destroyListeners(listeners);
        popper.destroy();
    };

    return {
        destroy,
    };
}

function getPopperOptions(anchor, options) {
    const placement = options.placement || anchor.dataset.placement || "bottom";

    return {
        placement,
        modifiers: {
            arrow: {
                element: ".tlp-popover-arrow",
            },
            computeStyle: {
                gpuAcceleration: false,
            },
        },
    };
}

function buildListeners(popover_trigger, popover_content, dismiss_buttons, options, popper) {
    const trigger = options.trigger || popover_trigger.dataset.trigger || "hover";
    if (trigger === "hover") {
        return [
            buildMouseOverListener(popover_trigger, popover_content, popper),
            buildMouseOutListener(popover_trigger, popover_content),
        ];
    }
    if (trigger === "click") {
        const listeners = [
            buildTriggerClickListener(popover_trigger, popover_content, popper),
            buildDocumentClickListener(popover_trigger, popover_content),
        ];
        for (const dismiss of dismiss_buttons) {
            listeners.push(buildDismissClickListener(dismiss));
        }
        return listeners;
    }

    return [];
}

function attachListeners(listeners) {
    for (const { element, type, handler } of listeners) {
        element.addEventListener(type, handler);
    }
}

function destroyListeners(listeners) {
    for (const { element, type, handler } of listeners) {
        element.removeEventListener(type, handler);
    }
}

function buildMouseOverListener(popover_trigger, popover_content, popper) {
    return {
        element: popover_trigger,
        type: "mouseover",
        handler() {
            hideAllShownPopovers();
            showPopover(popover_content, popper);
        },
    };
}

function buildMouseOutListener(popover_trigger, popover_content) {
    return {
        element: popover_trigger,
        type: "mouseout",
        handler() {
            hideAllShownPopovers();
            popover_content.classList.remove(CLASS_TLP_POPOVER_SHOWN);
        },
    };
}

function buildTriggerClickListener(popover_trigger, popover_content, popper) {
    return {
        element: popover_trigger,
        type: "click",
        handler() {
            const is_shown = popover_content.classList.contains(CLASS_TLP_POPOVER_SHOWN);
            hideAllShownPopovers();
            if (!is_shown) {
                popper.popper.setAttribute("x-trigger", "click");
                showPopover(popover_content, popper);
            }
        },
    };
}

function buildDocumentClickListener(popover_trigger, popover_content) {
    return {
        element: document,
        type: "click",
        handler(event) {
            if (
                popover_content.classList.contains(CLASS_TLP_POPOVER_SHOWN) &&
                findClosestElement(event.target, popover_content) === null &&
                findClosestElement(event.target, popover_trigger) === null
            ) {
                hideAllShownPopovers();
            }
        },
    };
}

function buildDismissClickListener(dismiss) {
    return {
        element: dismiss,
        type: "click",
        handler: hideAllShownPopovers,
    };
}

function hideAllShownPopovers() {
    for (const popover of document.querySelectorAll("." + CLASS_TLP_POPOVER_SHOWN)) {
        popover.classList.remove(CLASS_TLP_POPOVER_SHOWN);
    }
}

function showPopover(popover_content, popper) {
    popper.scheduleUpdate();
    popover_content.classList.add(CLASS_TLP_POPOVER_SHOWN);
}
