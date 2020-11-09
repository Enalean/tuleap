/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { get } from "tlp";
import { createListPicker } from "@tuleap/list-picker/src/list-picker";

export default MultiselectBoxController;

MultiselectBoxController.$inject = ["$element"];

function MultiselectBoxController($element) {
    var self = this;

    Object.assign(self, {
        $onInit: init,
        $onDestroy: destroy,
    });

    async function init() {
        if (self.is_list_picker_enabled) {
            const select = $element[0].querySelector("[data-select=list-picker-multiple]");
            self.destroy = await createListPicker(select, getOptions()).then((list_picker) => {
                return list_picker.destroy;
            });
        }
    }

    function destroy() {
        if (self.is_list_picker_enabled) {
            self.destroy();
        }
    }

    function getOptions() {
        if (self.field.bindings.type === "users") {
            return {
                items_template_formatter: async (value_id, label) => {
                    const value = value_id.split(":")[1];
                    if (value === "100") {
                        return label;
                    }

                    const response = await get(`/api/users/${encodeURIComponent(value)}`);
                    const user_representation = await response.json();
                    const avatar_url = user_representation.avatar_url;

                    return `<img class="tlp-avatar tlp-avatar-mini tuleap-artifact-modal-list-field-avatar-badge" src="${avatar_url}"/>${label}`;
                },
            };
        }

        return {};
    }
}
