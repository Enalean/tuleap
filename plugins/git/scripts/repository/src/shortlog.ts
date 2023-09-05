/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import { createDropdown } from "@tuleap/tlp-dropdown";

export default function initShortlog(): void {
    const shortlog = document.getElementById("git-repository-shortlog");
    if (!shortlog) {
        return;
    }

    let nb_shown_dropdown = 0;
    for (const button of shortlog.querySelectorAll(".commit-more-actions")) {
        const dropdown = createDropdown(button);
        dropdown.addEventListener("tlp-dropdown-shown", incrementNbShownDropdown);
        dropdown.addEventListener("tlp-dropdown-hidden", decrementNbShownDropdown);
    }

    shortlog.addEventListener("click", (event) => {
        redirectUserIfClickOnACard(event, shortlog, nb_shown_dropdown);
    });

    function incrementNbShownDropdown(): void {
        nb_shown_dropdown++;
    }

    function decrementNbShownDropdown(): void {
        nb_shown_dropdown--;
    }
}

function redirectUserIfClickOnACard(
    event: Event,
    shortlog: HTMLElement,
    nb_shown_dropdown: number,
): void {
    const card_element = getCommitCardTheUserHasClickedOn(event, shortlog, nb_shown_dropdown);
    if (!card_element) {
        return;
    }
    if (!card_element.dataset.href) {
        return;
    }

    window.location.href = card_element.dataset.href;
}

function getCommitCardTheUserHasClickedOn(
    event: Event,
    shortlog: HTMLElement,
    nb_shown_dropdown: number,
): null | HTMLElement {
    if (!(event.target instanceof HTMLElement)) {
        return null;
    }

    const element = event.target.closest(
        "#git-repository-shortlog, .git-repository-commit-card, a, button",
    );
    if (!(element instanceof HTMLElement)) {
        return null;
    }

    if (element === shortlog) {
        return null;
    }

    if (["a", "button"].indexOf(element.tagName.toLowerCase()) >= 0) {
        return null;
    }

    if (nb_shown_dropdown > 0) {
        return null;
    }

    if (!element.dataset.href) {
        return null;
    }

    return element;
}
