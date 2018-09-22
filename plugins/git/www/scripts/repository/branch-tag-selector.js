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

import TimeAgo from "javascript-time-ago";
import time_ago_english from "javascript-time-ago/locale/en";
import time_ago_french from "javascript-time-ago/locale/fr";

export default function initBranchTagSelector() {
    const button = document.getElementById("git-repository-branch-tag-selector-button");
    if (!button) {
        return;
    }

    const caret = button.querySelector(".fa-caret-down");

    if (!button.dataset.isUndefined) {
        TimeAgo.locale(time_ago_english);
        TimeAgo.locale(time_ago_french);
        const time_ago = new TimeAgo(document.body.dataset.userLocale.replace(/_/g, "-"));

        const committed_on = new Date(button.dataset.committerEpoch * 1000);

        const span = document.createElement("span");
        span.classList.add("git-repository-branch-tag-selector-button-time");
        span.innerText = time_ago.format(committed_on);

        button.insertBefore(span, caret);
    }
}
