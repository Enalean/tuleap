/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2010.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/* global $:readonly $F:readonly codendi:readonly tuleap:readonly */

/**
 * Observe default values set for each form field. If user changes the value,
 * displays the original value right after the form element.
 *
 * Link user status and Unix status: if admin turns account "Deleted" the
 * unix status is deleted as well.
 */
document.observe("dom:loaded", function () {
    // Observe form elements to show the previous value if there is a change
    var form = $(document["forms"]["update_user"]);
    form.getElements().each(function (elt) {
        // Get inital value
        var span = new Element("span", { class: "highlight" });
        var txt;
        if (elt.tagName.toUpperCase() == "SELECT") {
            txt = elt.options[elt.selectedIndex].text;
        } else {
            txt = $F(elt);
        }
        span.update(tuleap.escaper.html(txt));
        var container = new Element("span", { style: "margin-left: 1em;" });
        container.update(codendi.locales["admin_usergroup"].was + " ");
        container.appendChild(span);
        container.hide();
        elt.parentNode.appendChild(container);

        // When something change display original value if the new value is
        // different.
        var displaySpan = function () {
            var value;
            if (elt.tagName.toUpperCase() == "SELECT") {
                value = elt.options[elt.selectedIndex].text;
            } else {
                value = $F(elt);
            }
            if (value != span.textContent) {
                container.show();
            } else {
                container.hide();
            }
        };

        // Observe the right event
        elt.observe("change", displaySpan);
        if (elt.tagName.toUpperCase() == "INPUT") {
            elt.observe("keyup", displaySpan);
        }
    });

    // Bind account status and unix status.
    var form_status = $(document["forms"]["update_user"]["form_status"]);
    form_status.observe("change", function () {
        var form_unixstatus = $(document["forms"]["update_user"]["form_unixstatus"]);
        var val = $F(form_status);
        switch (val) {
            case "S":
            case "D":
                form_unixstatus.setValue(val);
                break;
        }
    });
});
