/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

export function init(
    doc: HTMLDocument,
    options_container: HTMLElement,
    start_date_select_box: HTMLSelectElement,
    end_date_select_box: HTMLSelectElement,
    option_end_date_radio_button: HTMLInputElement,
    option_duration_radio_button: HTMLInputElement
): void {
    registerEvents(
        doc,
        options_container,
        start_date_select_box,
        end_date_select_box,
        option_end_date_radio_button,
        option_duration_radio_button
    );

    initSelectBoxBindings(start_date_select_box, end_date_select_box, option_end_date_radio_button);

    toggleSelectBoxes(doc);
}

function registerEvents(
    doc: HTMLDocument,
    options_container: HTMLElement,
    start_date_select_box: HTMLSelectElement,
    end_date_select_box: HTMLSelectElement,
    option_end_date_radio_button: HTMLInputElement,
    option_duration_radio_button: HTMLInputElement
): void {
    options_container.addEventListener("click", () => toggleSelectBoxes(doc));

    start_date_select_box.addEventListener("change", () =>
        disableAlreadySelectedDateFields(
            start_date_select_box,
            end_date_select_box,
            option_end_date_radio_button
        )
    );
    end_date_select_box.addEventListener("change", () =>
        disableAlreadySelectedDateFields(
            end_date_select_box,
            start_date_select_box,
            option_end_date_radio_button
        )
    );
    option_end_date_radio_button.addEventListener("click", () =>
        initSelectBoxBindings(
            start_date_select_box,
            end_date_select_box,
            option_end_date_radio_button
        )
    );

    option_duration_radio_button.addEventListener("click", () => {
        enableAllOptions([start_date_select_box, end_date_select_box]);
        end_date_select_box.options[0].selected = true;
    });
}

function initSelectBoxBindings(
    start_date_select_box: HTMLSelectElement,
    end_date_select_box: HTMLSelectElement,
    option_end_date_radio_button: HTMLInputElement
): void {
    disableAlreadySelectedDateFields(
        start_date_select_box,
        end_date_select_box,
        option_end_date_radio_button
    );
    disableAlreadySelectedDateFields(
        end_date_select_box,
        start_date_select_box,
        option_end_date_radio_button
    );
}

function toggleSelectBoxes(doc: HTMLDocument): void {
    const options: NodeListOf<HTMLInputElement> = doc.querySelectorAll(
        ".semantic-timeframe-option-radio"
    );

    for (const radio_button of options) {
        const target_select = radio_button.dataset.targetSelector;
        if (!target_select) {
            return;
        }

        const selector = doc.getElementById(target_select);
        if (!(selector instanceof HTMLSelectElement)) {
            continue;
        }

        if (radio_button.checked) {
            selector.disabled = false;
            selector.required = true;

            showAsterisk(doc, radio_button);
        } else {
            selector.disabled = true;
            selector.required = false;

            hideAsterisk(doc, radio_button);
        }
    }
}

function hideAsterisk(doc: HTMLDocument, radio_button: HTMLInputElement): void {
    const asterisk = doc.querySelector(`#${radio_button.id} ~ .highlight`);
    if (!asterisk) {
        return;
    }

    asterisk.classList.add("tracker-administration-semantic-timeframe-option-not-required");
}

function showAsterisk(doc: HTMLDocument, radio_button: HTMLInputElement): void {
    const asterisk = doc.querySelector(`#${radio_button.id} ~ .highlight`);
    if (!asterisk) {
        return;
    }

    asterisk.classList.remove("tracker-administration-semantic-timeframe-option-not-required");
}

function disableAlreadySelectedDateFields(
    current_select_box: HTMLSelectElement,
    target_select_box: HTMLSelectElement,
    option_end_date_radio_button: HTMLInputElement
): void {
    if (!option_end_date_radio_button.checked) {
        return;
    }

    const current_select_box_value = getNumberValue(current_select_box);
    if (!current_select_box_value) {
        enableAllOptions([target_select_box]);

        return;
    }

    for (const option of target_select_box.options) {
        const option_value = getNumberValue(option);
        option.disabled = Boolean(option_value && option_value === current_select_box_value);
    }
}

function getNumberValue(element: HTMLSelectElement | HTMLOptionElement): number {
    return Number(element.value);
}

function enableAllOptions(select_boxes: HTMLSelectElement[]): void {
    select_boxes.forEach((select_box) => {
        for (const option of select_box.options) {
            option.disabled = false;
        }
    });
}
