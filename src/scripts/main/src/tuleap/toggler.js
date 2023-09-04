/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2011. All rights reserved
 * Copyright (c) Enalean SAS, 2011 - Present. All rights reserved
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

import { get } from "@tuleap/tlp-fetch";

let before_listeners = [];

export const init = (element, force_display, force_ajax) => {
    if (!element) {
        return;
    }

    const togglers = element.querySelectorAll(
        ".toggler, .toggler-hide, .toggler-noajax, .toggler-hide-noajax",
    );
    [].forEach.call(togglers, (toggler) => {
        load(toggler, force_display, force_ajax);
    });
};

export const addBeforeListener = (callback) => {
    before_listeners.push(callback);
};

function load(toggler, force_display, force_ajax) {
    if (force_display) {
        const was_noajax =
            toggler.classList.contains("toggler-hide-noajax") ||
            toggler.classList.contains("toggler-noajax");

        removeExistingTogglerClass(toggler);
        addTogglerClassWhenForceDisplay(force_display, was_noajax, toggler);
    }

    if (force_ajax) {
        const was_hide =
            toggler.classList.contains("toggler-hide") ||
            toggler.classList.contains("toggler-hide-noajax");

        removeExistingTogglerClass(toggler);
        addTogglerClassWhenForceAjax(force_ajax, was_hide, toggler);
    }

    toggler.addEventListener("click", listenClickOnToggle);
}

function removeExistingTogglerClass(toggler) {
    toggler.classList.remove("toggler-hide");
    toggler.classList.remove("toggler-hide-noajax");
    toggler.classList.remove("toggler");
    toggler.classList.remove("toggler-noajax");
}

function addTogglerClassWhenForceDisplay(force_display, was_noajax, toggler) {
    if (force_display === "show") {
        if (was_noajax) {
            toggler.classList.add("toggler");
        } else {
            toggler.classList.add("toggler-noajax");
        }
    } else {
        if (was_noajax) {
            toggler.classList.add("toggler-hide");
        } else {
            toggler.classList.add("toggler-hide-noajax");
        }
    }
}

function addTogglerClassWhenForceAjax(force_ajax, was_hide, toggler) {
    if (force_ajax === "ajax") {
        if (was_hide) {
            toggler.classList.add("toggler-hide");
        } else {
            toggler.classList.add("toggler");
        }
    } else {
        if (was_hide) {
            toggler.classList.add("toggler-hide-noajax");
        } else {
            toggler.classList.add("toggler-noajax");
        }
    }
}

function listenClickOnToggle(evt) {
    const toggler = this,
        is_collapsing =
            toggler.classList.contains("toggler") || toggler.classList.contains("toggler-noajax");

    before(evt, toggler, is_collapsing);

    //toggle the state
    if (
        toggler.classList.contains("toggler-noajax") ||
        toggler.classList.contains("toggler-hide-noajax")
    ) {
        toggler.classList.toggle("toggler-noajax");
        toggler.classList.toggle("toggler-hide-noajax");
    } else {
        toggler.classList.toggle("toggler");
        toggler.classList.toggle("toggler-hide");
        //save the state with ajax only if the toggler has an id
        if (toggler.id) {
            get("/toggler.php?id=" + encodeURIComponent(toggler.id));
        }
    }
}

function before(evt, toggler, is_collapsing) {
    before_listeners.forEach((callback) => {
        callback(evt, toggler, is_collapsing);
    });
}
