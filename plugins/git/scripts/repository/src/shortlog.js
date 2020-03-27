/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import { dropdown as createDropdown } from "tlp";

export default function initShortlog() {
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

    function incrementNbShownDropdown() {
        nb_shown_dropdown++;
    }

    function decrementNbShownDropdown() {
        nb_shown_dropdown--;
    }
}

function redirectUserIfClickOnACard(event, shortlog, nb_shown_dropdown) {
    const card_element = getCommitCardTheUserHasClickedOn(event, shortlog, nb_shown_dropdown);
    if (card_element) {
        window.location.href = card_element.dataset.href;
    }
}

function getCommitCardTheUserHasClickedOn(event, shortlog, nb_shown_dropdown) {
    const element = event.target.closest(
        "#git-repository-shortlog, .git-repository-commit-card, a, button"
    );
    if (!element) {
        return;
    }

    if (element === shortlog) {
        return;
    }

    if (["a", "button"].indexOf(element.tagName.toLowerCase()) >= 0) {
        return;
    }

    if (nb_shown_dropdown > 0) {
        return;
    }

    if (!element.dataset.href) {
        return;
    }

    return element;
}
