/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { getHTMLInputElementFromId } from "./HTML_element_extractor";

export function enableInputLabelIterationWhenAnIterationTrackerIsSelected(doc: Document): void {
    const label_iteration = getHTMLInputElementFromId(
        doc,
        "admin-configuration-iteration-label-section"
    );
    const sub_label_iteration = getHTMLInputElementFromId(
        doc,
        "admin-configuration-iteration-sub-label-section"
    );

    label_iteration.parentElement?.classList.remove("tlp-form-element-disabled");
    sub_label_iteration.parentElement?.classList.remove("tlp-form-element-disabled");

    label_iteration.disabled = false;
    sub_label_iteration.disabled = false;
}

export function disableInputLabelIterationWhenNoSelectedIterationTracker(doc: Document): void {
    const label_iteration = getHTMLInputElementFromId(
        doc,
        "admin-configuration-iteration-label-section"
    );
    const sub_label_iteration = getHTMLInputElementFromId(
        doc,
        "admin-configuration-iteration-sub-label-section"
    );

    label_iteration.parentElement?.classList.add("tlp-form-element-disabled");
    sub_label_iteration.parentElement?.classList.add("tlp-form-element-disabled");

    label_iteration.disabled = true;
    sub_label_iteration.disabled = true;
}
