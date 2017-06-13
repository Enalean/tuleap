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

import { render } from 'mustache';
import { sanitize } from 'dompurify';
import { get, put } from 'tlp';

document.addEventListener('DOMContentLoaded', function() {
    var user_history_dropdown_trigger = document.querySelector('#nav-dropdown-user-history > .nav-dropdown-link'),
        history_content               = document.getElementById('nav-dropdown-content-user-history-content'),
        clear                         = document.getElementById('nav-dropdown-content-user-history-clear'),
        clear_button                  = document.getElementById('nav-dropdown-content-user-history-clear-button'),
        loading_history               = document.getElementById('nav-dropdown-content-user-history-loading'),
        empty_history                 = document.getElementById('nav-dropdown-content-user-history-empty');

    if (! user_history_dropdown_trigger) {
        return;
    }

    function getUserHistoryUrl() {
        return '/api/v1/users/' + history_content.dataset.userId + '/history';
    }

    user_history_dropdown_trigger.addEventListener('click', loadHistoryAsynchronously);
    clear_button.addEventListener('click', clearHistory);

    function clearHistory() {
        put(getUserHistoryUrl(), {
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({history_entries: []}),
        }).then(switchToEmptyState);
    }

    function loadHistoryAsynchronously() {
        get(getUserHistoryUrl())
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.entries.length > 0) {
                    buildHistoryItems(data.entries);
                    switchToLoadedState();
                } else {
                    switchToEmptyState();
                }
                user_history_dropdown_trigger.removeEventListener('click', loadHistoryAsynchronously);
            });
    }

    function buildHistoryItems(entries) {
        const template = document.getElementById('history-item-placeholder').textContent;
        const rendered_history = render(template, { entries: normalize(entries) });

        insertRenderedHistoryInDOM(rendered_history);
        addHistoryItemListeners();
    }

    function normalize(entries) {
        const all_underscores = /_/g;

        entries.forEach(function (entry) {
            entry.color_name = entry.color_name.replace(all_underscores, '-');
        });

        return entries;
    }

    function insertRenderedHistoryInDOM(rendered_history) {
        const purified_history = sanitize(rendered_history, { RETURN_DOM_FRAGMENT: true });

        history_content.appendChild(purified_history);
    }

    function addHistoryItemListeners() {
        const extra_links = history_content.querySelectorAll('.history-item-content-quick-links-link, .history-item-content-project');
        [].forEach.call(extra_links, (extra_link) => {
            extra_link.addEventListener('click', (event) => {
                event.stopPropagation();
                event.preventDefault();
                window.location.href = extra_link.dataset.href;
            });
        });
    }

    function switchToLoadedState() {
        loading_history.classList.remove('shown');
        history_content.classList.add('shown');
        clear.classList.add('shown');
        empty_history.classList.remove('shown');
    }

    function switchToEmptyState() {
        loading_history.classList.remove('shown');
        history_content.classList.remove('shown');
        clear.classList.remove('shown');
        empty_history.classList.add('shown');
    }
});
