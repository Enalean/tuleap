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

import TimeAgo from "javascript-time-ago";
import time_ago_english from "javascript-time-ago/locale/en";
import time_ago_french from "javascript-time-ago/locale/fr";

export default function initBranchTagSelector(): void {
    const button = document.getElementById("git-repository-branch-tag-selector-button");
    if (!button) {
        return;
    }

    const caret = button.querySelector(".fa-caret-down");

    if (!caret) {
        throw new Error("Button is not found");
    }

    if (!button.dataset.isUndefined) {
        TimeAgo.locale(time_ago_english);
        TimeAgo.locale(time_ago_french);

        const user_locale = document.body.dataset.userLocale;

        if (!user_locale) {
            return;
        }

        const comitter_epoch = button.dataset.committerEpoch;

        if (!comitter_epoch) {
            throw new Error("Commiter is invalid");
        }

        const time_ago = new TimeAgo(user_locale.replace(/_/g, "-"));

        const committed_on = new Date(Number.parseInt(comitter_epoch, 10) * 1000);

        const span = document.createElement("span");
        span.classList.add("git-repository-branch-tag-selector-button-time");
        span.innerText = time_ago.format(committed_on);

        button.insertBefore(span, caret);
    }

    const mount_point = document.createElement("div");

    if (!button.parentNode) {
        throw new Error("Parent Button is not found");
    }

    button.parentNode.insertBefore(mount_point, button.nextSibling);

    button.addEventListener("click", async () => {
        if (button.classList.contains("git-repository-branch-tag-selector-button-loaded")) {
            return;
        }
        if (button.classList.contains("disabled")) {
            return;
        }

        caret.classList.remove("fa-caret-down");
        caret.classList.add("fa-spin", "fa-spinner");
        button.classList.add("disabled");
        try {
            const { init } = await import("./branch-tag-selector/src/index");
            await init(mount_point, button);

            button.classList.add("git-repository-branch-tag-selector-button-loaded");
        } finally {
            caret.classList.add("fa-caret-down");
            caret.classList.remove("fa-spin", "fa-spinner");
            button.classList.remove("disabled");
        }
    });
}
