/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("semantic-timeframe-options");
    const start_date_selectbox = document.getElementById("start-date");
    const end_date_selectbox = document.getElementById("end-date-field");
    const option_end_date_radio_button = document.getElementById("option-end-date");
    const option_duration_radio_button = document.getElementById("option-duration");

    registerEvents(
        container,
        start_date_selectbox,
        end_date_selectbox,
        option_end_date_radio_button,
        option_duration_radio_button
    );

    initSelectboxBindings(start_date_selectbox, end_date_selectbox, option_end_date_radio_button);

    toggleSelectboxes();
});

function registerEvents(
    container,
    start_date_selectbox,
    end_date_selectbox,
    option_end_date_radio_button,
    option_duration_radio_button
) {
    container.addEventListener("click", toggleSelectboxes);

    start_date_selectbox.addEventListener("change", () =>
        disableAlreadySelectedDateFields(
            start_date_selectbox,
            end_date_selectbox,
            option_end_date_radio_button
        )
    );
    end_date_selectbox.addEventListener("change", () =>
        disableAlreadySelectedDateFields(
            end_date_selectbox,
            start_date_selectbox,
            option_end_date_radio_button
        )
    );
    option_end_date_radio_button.addEventListener("click", () =>
        initSelectboxBindings(
            start_date_selectbox,
            end_date_selectbox,
            option_end_date_radio_button
        )
    );

    option_duration_radio_button.addEventListener("click", () => {
        enableAllOptions([start_date_selectbox, end_date_selectbox]);
        end_date_selectbox.children[0].selected = true;
    });
}

function initSelectboxBindings(
    start_date_selectbox,
    end_date_selectbox,
    option_end_date_radio_button
) {
    disableAlreadySelectedDateFields(
        start_date_selectbox,
        end_date_selectbox,
        option_end_date_radio_button
    );
    disableAlreadySelectedDateFields(
        end_date_selectbox,
        start_date_selectbox,
        option_end_date_radio_button
    );
}

function toggleSelectboxes() {
    const options = document.querySelectorAll(".semantic-timeframe-option-radio");

    for (const radio_button of options) {
        const selector = document.getElementById(radio_button.dataset.targetSelector);

        if (radio_button.checked) {
            selector.disabled = false;
            selector.required = true;

            showAsterix(radio_button);
        } else {
            selector.disabled = true;
            selector.required = false;

            hideAsterix(radio_button);
        }
    }
}

function hideAsterix(radio_button) {
    const asterix = document.querySelector(`#${radio_button.id} ~ .highlight`);

    if (!asterix) {
        return;
    }

    asterix.classList.add("tracker-administration-semantic-timeframe-option-not-required");
}

function showAsterix(radio_button) {
    const asterix = document.querySelector(`#${radio_button.id} ~ .highlight`);

    if (!asterix) {
        return;
    }

    asterix.classList.remove("tracker-administration-semantic-timeframe-option-not-required");
}

function disableAlreadySelectedDateFields(
    current_selectbox,
    target_selectbox,
    option_end_date_radio_button
) {
    if (!option_end_date_radio_button.checked) {
        return;
    }

    const current_selectbox_value = getNumberValue(current_selectbox);

    if (!current_selectbox_value) {
        enableAllOptions([target_selectbox]);

        return;
    }

    for (const option of target_selectbox.children) {
        const option_value = getNumberValue(option);
        if (option_value && option_value === current_selectbox_value) {
            option.disabled = true;
        }
    }
}

function getNumberValue(element) {
    return Number(element.value);
}

function enableAllOptions(selectboxes) {
    selectboxes.forEach((selectbox) => {
        for (const option of selectbox.children) {
            option.disabled = false;
        }
    });
}
