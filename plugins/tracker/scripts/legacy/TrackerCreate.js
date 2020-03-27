/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/* global $:readonly $$:readonly */

document.observe("dom:loaded", function () {
    function displayMode(mode) {
        mode.up("h3").next(".tracker_create_mode").show();
    }

    function hideAllModes() {
        $$(".tracker_create_mode").invoke("hide");
    }

    hideAllModes();
    $$("input[name=create_mode]").each(function (mode) {
        if (mode.checked) {
            displayMode(mode);
        }
        mode.observe("click", function () {
            hideAllModes();
            displayMode(mode);
        });
    });

    // read xml template that has been choosen and prefill the form (name, desc, …)
    // Depends on presence of FileReader api (bye bye IE)
    var input_file = $("tracker_new_xml_file");
    if (Boolean(window.FileReader) && input_file) {
        var file_reader = new FileReader(),
            filter = /^text\/xml$/;

        file_reader.onload = function (event) {
            var parsed = new DOMParser().parseFromString(event.target.result, "text/xml"),
                trackers = parsed.getElementsByTagName("tracker"),
                tracker = trackers[0] || undefined;

            if (!tracker) {
                //eslint-disable-next-line no-alert
                alert("You must select a valid template xml file!");
                return;
            }

            $("newtracker_name").highlight().value = tracker.getElementsByTagName(
                "name"
            )[0].textContent;
            $("newtracker_description").highlight().value = tracker.getElementsByTagName(
                "description"
            )[0].textContent;
            $("newtracker_itemname").highlight().value = tracker.getElementsByTagName(
                "item_name"
            )[0].textContent;
        };

        input_file.observe("change", function () {
            if (input_file.files.length === 0) {
                return;
            }
            var file = input_file.files[0];
            if (!filter.test(file.type)) {
                //eslint-disable-next-line no-alert
                alert("Not an xml file! (" + file.type + ")");
                return;
            }

            file_reader.readAsText(file);
        });
    }
});
