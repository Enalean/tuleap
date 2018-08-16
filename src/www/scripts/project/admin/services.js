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

import { modal as createModal } from "tlp";

document.addEventListener("click", event => {
    const button = event.target;
    const is_add_button = button.id === "project-admin-services-add-button";
    const allowed_classes = [
        "project-admin-services-edit-button",
        "project-admin-services-delete-button"
    ];
    const is_button_classlist_contain_allowed_class = allowed_classes.some(classname =>
        button.classList.contains(classname)
    );

    if (is_add_button || is_button_classlist_contain_allowed_class) {
        const modal = createModal(document.getElementById(button.dataset.targetModalId), {
            destroy_on_hide: true
        });

        modal.show();
    }
});
