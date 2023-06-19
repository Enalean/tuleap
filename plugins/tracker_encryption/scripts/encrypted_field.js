/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

document.addEventListener("DOMContentLoaded", function () {
    var encrypted_fields = document.getElementsByClassName("encrypted-field");
    [].forEach.call(encrypted_fields, function (element) {
        var password_field = element.children[0];
        var button_field = element.children[1];
        var icon_field = button_field.children[0];

        button_field.addEventListener("mousedown", function () {
            password_field.type = "text";
            icon_field.className = "fa fa-eye";
        });

        button_field.addEventListener("mouseup", function () {
            password_field.type = "password";
            icon_field.className = "fa fa-eye-slash";
        });

        button_field.addEventListener("mouseout", function () {
            password_field.type = "password";
            icon_field.className = "fa fa-eye-slash";
        });
    });
});
