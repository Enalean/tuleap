/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

import { createDatePicker, getLocaleWithDefault } from "@tuleap/tlp-date-picker";
import { select2 } from "tlp";
import { autocomplete_users_for_select2 } from "@tuleap/autocomplete-for-select2";

document.addEventListener("DOMContentLoaded", () => {
    const sub_events_panels = document.querySelectorAll(
        ".siteadmin-project-history-filter-form-subevents",
    );
    const events = document.getElementById("siteadmin-project-history-events");
    const by_filter = document.getElementById("siteadmin-project-history-by");
    const locale = getLocaleWithDefault(document);

    if (events) {
        displayCurrentSubEventsPanel();

        events.addEventListener("change", () => {
            for (const panel of sub_events_panels) {
                if (!(panel instanceof HTMLElement)) {
                    continue;
                }
                const box = panel.querySelector("select");
                if (box) {
                    panel.style.display = "none";
                    box.disabled = true;
                    for (const option of box.options) {
                        option.selected = false;
                    }
                }
            }

            displayCurrentSubEventsPanel();
        });
    }

    if (by_filter) {
        autocomplete_users_for_select2(by_filter, { internal_users_only: 1 });
    }

    const datepickers = document.querySelectorAll(".tlp-input-date");
    for (const element of datepickers) {
        if (element instanceof HTMLInputElement) {
            createDatePicker(element, locale);
        }
    }

    function displayCurrentSubEventsPanel(): void {
        if (!events || !(events instanceof HTMLSelectElement) || !events.options) {
            return;
        }

        const target = events.options[events.selectedIndex].dataset.target;
        if (!target) {
            return;
        }
        const panel = document.getElementById(target);
        if (!panel) {
            return;
        }

        const box = panel.querySelector("select");
        if (!box) {
            return;
        }

        panel.style.display = "block";
        box.disabled = false;
        initSelect2(box);
    }

    function initSelect2(box: HTMLSelectElement): void {
        select2(box, {
            placeholder: box.dataset.placeholder,
            allowClear: true,
        });
    }
});
