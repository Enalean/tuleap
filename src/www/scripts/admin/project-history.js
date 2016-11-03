/**
 * Copyright (c) Enalean SAS - 2016. All rights reserved
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

document.addEventListener('DOMContentLoaded', function() {

    var sub_events_panels = document.querySelectorAll('.siteadmin-project-history-filter-form-subevents'),
        events            = document.getElementById('siteadmin-project-history-events'),
        by_filter         = document.getElementById('siteadmin-project-history-by');

    if (events) {
        displayCurrentSubEventsPanel();

        events.addEventListener('change', function () {
            [].forEach.call(sub_events_panels, function (panel) {
                var box = panel.querySelector('select');

                panel.style.display = 'none';
                box.disabled = true;
                [].forEach.call(box.options, function (option) {
                    option.selected = false;
                });
            });

            displayCurrentSubEventsPanel();
        });
    }

    if (by_filter) {
        tuleap.autocomplete_users_for_select2(by_filter, { internal_users_only: 1 });
    }

    var datepickers = document.querySelectorAll('.tlp-input-date');
    [].forEach.call(datepickers, function (element) {
        tlp.datePicker(element);
    });

    function displayCurrentSubEventsPanel() {
        var panel = document.getElementById(events.options[events.selectedIndex].dataset.target);
        if (! panel) {
            return;
        }

        var box = panel.querySelector('select');

        panel.style.display = 'block';
        box.disabled = false;
        initSelect2(box);
    }

    function initSelect2(box) {
        tlp.select2(box, {
            placeholder: box.dataset.placeholder,
            allowClear: true
        });
    }
});
