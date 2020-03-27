/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

import buildHistoryItems from "./history-items-builder.js";

export default function init(get, put, user_history_dropdown_trigger) {
    const controller = new NavbarHistoryController(get, put);

    const loadHistoryAsynchronouslyOneTimeListener = () => {
        controller.loadHistoryAsynchronously().then(() => {
            user_history_dropdown_trigger.removeEventListener(
                "click",
                loadHistoryAsynchronouslyOneTimeListener
            );
        });
    };
    user_history_dropdown_trigger.addEventListener(
        "click",
        loadHistoryAsynchronouslyOneTimeListener
    );

    const clear_button = document.getElementById("nav-dropdown-content-user-history-clear-button");
    clear_button.addEventListener("click", (event) => {
        event.stopPropagation();
        clear_button.disabled = true;
        controller.clearHistory().then(() => {
            clear_button.disabled = false;
        });
    });
}

class NavbarHistoryController {
    constructor(get, put) {
        this.clear = document.getElementById("nav-dropdown-content-user-history-clear");
        this.loading_history = document.getElementById("nav-dropdown-content-user-history-loading");
        this.error_message_fetch = document.getElementById(
            "nav-dropdown-content-user-history-error-message-fetch"
        );
        this.error_message_clear = document.getElementById(
            "nav-dropdown-content-user-history-error-message-clear"
        );
        this.empty_history = document.getElementById("nav-dropdown-content-user-history-empty");
        this.history_content = document.getElementById("nav-dropdown-content-user-history-content");

        this.get = get;
        this.put = put;
    }

    loadHistoryAsynchronously() {
        const url = this.getUserHistoryUrl();
        return this.get(url)
            .then((response) => response.json())
            .then((data) => {
                if (data.entries.length > 0) {
                    buildHistoryItems(data.entries, this.history_content);
                    this.switchToLoadedState();
                } else {
                    this.switchToEmptyState();
                }
            })
            .catch(() => this.switchToErrorStateForFetch());
    }

    clearHistory() {
        const url = this.getUserHistoryUrl();
        return this.put(url, {
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ history_entries: [] }),
        })
            .then(() => this.switchToEmptyState())
            .catch(() => this.switchToErrorStateForClear());
    }

    getUserHistoryUrl() {
        return "/api/v1/users/" + this.history_content.dataset.userId + "/history";
    }

    hideAll() {
        this.error_message_clear.classList.remove("shown");
        this.error_message_fetch.classList.remove("shown");
        this.loading_history.classList.remove("shown");
        this.history_content.classList.remove("shown");
        this.clear.classList.remove("shown");
        this.empty_history.classList.remove("shown");
    }

    switchToLoadedState() {
        this.hideAll();
        this.history_content.classList.add("shown");
        this.clear.classList.add("shown");
    }

    switchToEmptyState() {
        this.hideAll();
        this.empty_history.classList.add("shown");
    }

    switchToErrorStateForClear() {
        this.hideAll();
        this.error_message_clear.classList.add("shown");
    }

    switchToErrorStateForFetch() {
        this.hideAll();
        this.error_message_fetch.classList.add("shown");
    }
}
