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

import Gettext from "node-gettext";
import french_translations from "../po/fr.po";

export default function initCopyButton() {
    const copy_button = document.getElementById("git-repository-copy-url");
    if (!copy_button) {
        return;
    }

    const gettext_provider = new Gettext();
    gettext_provider.addTranslations("fr_FR", "git", french_translations);
    const locale = document.body.dataset.userLocale;
    gettext_provider.setLocale(locale);

    const input = document.getElementById("git-repository-clone-input");

    const original_title = copy_button.getAttribute("data-tlp-tooltip");

    copy_button.addEventListener("click", function () {
        input.select();
        document.execCommand("copy");
        copy_button.setAttribute(
            "data-tlp-tooltip",
            gettext_provider.gettext("URL have been copied")
        );

        removeTooltipDisplay(copy_button, original_title);
    });
}

function removeTooltipDisplay(copy_button, original_title) {
    setTimeout(function () {
        copy_button.setAttribute("data-tlp-tooltip", original_title);
    }, 5000);
}
