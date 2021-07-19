/*
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

import { sprintf } from "sprintf-js";
import type { RetrieveElement } from "../dom/RetrieveElement";
import type { TimeboxLabel } from "../dom/TimeboxLabel";
import type { GettextProvider } from "../GettextProvider";

const LABEL_SELECTOR = "[data-program-increment-label]";
const NEW_LABEL_SELECTOR = "[data-program-increment-label-new]";
const EXAMPLE_LABEL_SELECTOR = "[data-program-increment-label-example]";

export function initPreview(
    retriever: RetrieveElement,
    gettext_provider: GettextProvider,
    label_input: TimeboxLabel,
    sub_label_input: TimeboxLabel
): void {
    const default_program_increments_label = gettext_provider.gettext("Program Increments");
    const default_program_increments_sub_label = gettext_provider.gettext("program increment");

    const label_element = retriever.querySelector(LABEL_SELECTOR);
    if (!label_element) {
        return;
    }
    const new_label_element = retriever.querySelector(NEW_LABEL_SELECTOR);
    if (!new_label_element) {
        return;
    }
    const example_labels = retriever.querySelectorAll(EXAMPLE_LABEL_SELECTOR);

    const defaultSubLabelValue = (value: string): string =>
        value !== "" ? value : default_program_increments_sub_label;

    const changeLabel = (value: string): void => {
        const defaulted_value = value !== "" ? value : default_program_increments_label;
        label_element.textContent = defaulted_value;
    };
    const changeNewLabel = (value: string): void => {
        const defaulted_value = defaultSubLabelValue(value);
        new_label_element.textContent = sprintf(
            gettext_provider.gettext("New %s"),
            defaulted_value
        );
    };
    const changeExampleLabels = (value: string): void => {
        const defaulted_value = defaultSubLabelValue(value);
        for (let i = 0; i < example_labels.length; i++) {
            const decreasing_iteration_number = example_labels.length - i;
            example_labels[i].textContent = `${defaulted_value} ${decreasing_iteration_number}`;
        }
    };

    changeLabel(label_input.value);
    label_input.addInputListener(changeLabel);

    changeNewLabel(sub_label_input.value);
    changeExampleLabels(sub_label_input.value);
    sub_label_input.addInputListener(changeNewLabel);
    sub_label_input.addInputListener(changeExampleLabels);
}
