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

    container.addEventListener("click", toggleSelectboxes);

    toggleSelectboxes();
});

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
