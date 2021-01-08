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
import { createModal } from "tlp";
import { injectClasses } from "./illustration-inject-class-helper";

const illustration_helper_button = document.getElementById("doc-colors-illustration-helper-button");
const illustration_helper_source = document.getElementById("illustration-helper-source");
const illustration_helper_target = document.getElementById("illustration-helper-target");
const illustration_helper_preview = document.getElementById(
    "doc-colors-illustration-helper-modal-preview-block"
);

if (illustration_helper_button) {
    const illustration_helper_modal = createModal(
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
