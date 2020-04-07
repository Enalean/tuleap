/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import { sanitize } from "dompurify";
import { modal } from "tlp";

const illustration_helper_button = document.getElementById("doc-colors-illustration-helper-button");
const illustration_helper_source = document.getElementById("illustration-helper-source");
const illustration_helper_target = document.getElementById("illustration-helper-target");
const illustration_helper_preview = document.getElementById(
    "doc-colors-illustration-helper-modal-preview-block"
);

const primary_color = "#92cee5";
const secondary_light_color = "#e0f6ff";
const secondary_dark_color = "#c1edff";
const tertiary_color = "#d5e8f3";

if (illustration_helper_button) {
    const illustration_helper_modal = modal(
        document.getElementById("doc-colors-illustration-helper-modal"),
        {}
    );
    illustration_helper_button.addEventListener("click", function () {
        illustration_helper_modal.toggle();
    });

    setInterval(generateSVG, 300);
}

function generateSVG() {
    illustration_helper_preview.innerHTML = sanitize(illustration_helper_source.value, {
        USE_PROFILES: { svg: true, svgFilters: true },
    });
    injectClasses(illustration_helper_preview.children);
    illustration_helper_target.value = illustration_helper_preview.innerHTML;
}

function injectClasses(elements) {
    const length = elements.length;
    if (length === 0) {
        return;
    }

    for (let i = 0; i < length; i++) {
        const element = elements[i];
        if (typeof element.classList !== "undefined") {
            const stroke = element.getAttribute("stroke");
            const fill = element.getAttribute("fill");

            element.classList.remove(
                "tlp-illustration-primary-box",
                "tlp-illustration-primary-box",
                "tlp-illustration-primary-stroke",
                "tlp-illustration-secondary-light-stroke",
                "tlp-illustration-secondary-dark-stroke",
                "tlp-illustration-tertiary-stroke",
                "tlp-illustration-primary-box",
                "tlp-illustration-secondary-light-fill",
                "tlp-illustration-secondary-dark-fill",
                "tlp-illustration-tertiary-fill"
            );
            if (
                String(stroke).toLowerCase() === primary_color &&
                String(fill).toLowerCase() === secondary_light_color
            ) {
                element.classList.add("tlp-illustration-primary-box");
            } else if (
                String(stroke).toLowerCase() === primary_color &&
                String(fill).toLowerCase() === secondary_dark_color
            ) {
                element.classList.add("tlp-illustration-primary-dark-box");
            } else if (String(stroke).toLowerCase() === primary_color) {
                element.classList.add("tlp-illustration-primary-stroke");
            } else if (String(stroke).toLowerCase() === secondary_light_color) {
                element.classList.add("tlp-illustration-secondary-light-stroke");
            } else if (String(stroke).toLowerCase() === secondary_dark_color) {
                element.classList.add("tlp-illustration-secondary-dark-stroke");
            } else if (String(stroke).toLowerCase() === tertiary_color) {
                element.classList.add("tlp-illustration-tertiary-stroke");
            } else if (String(fill).toLowerCase() === primary_color) {
                element.classList.add("tlp-illustration-primary-fill");
            } else if (String(fill).toLowerCase() === secondary_light_color) {
                element.classList.add("tlp-illustration-secondary-light-fill");
            } else if (String(fill).toLowerCase() === secondary_dark_color) {
                element.classList.add("tlp-illustration-secondary-dark-fill");
            } else if (String(fill).toLowerCase() === tertiary_color) {
                element.classList.add("tlp-illustration-tertiary-fill");
            }
        }

        injectClasses(element.children);
    }
}
