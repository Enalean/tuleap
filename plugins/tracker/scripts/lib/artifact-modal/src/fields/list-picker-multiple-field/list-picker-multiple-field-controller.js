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

import { createListPicker } from "@tuleap/list-picker";
import { sanitize } from "dompurify";
import { DidChangeListFieldValue } from "../../domain/fields/select-box-field/DidChangeListFieldValue";

export default MultiselectBoxController;

MultiselectBoxController.$inject = ["$element", "$timeout"];

function MultiselectBoxController($element, $timeout) {
    var self = this;

    Object.assign(self, {
        $onInit: $timeout(init, 0),
        $onDestroy: destroy,
        isFieldValid,
        onValueChange,
    });

    const select = $element[0].querySelector("[data-select=list-picker-multiple]");

    function init() {
        bindOptionsDecorators();

        self.event_dispatcher.addObserver("DidChangeAllowedValues", (event) => {
            if (event.field_id !== self.field.field_id) {
                return;
            }

            self.value_model.bind_value_ids = self.value_model.bind_value_ids.filter(
                (bind_value_id) => event.allowed_bind_value_ids.includes(bind_value_id)
            );
            if (event.allowed_bind_value_ids.length === 1) {
                self.value_model.bind_value_ids = [event.allowed_bind_value_ids[0]];
            }

            $timeout(() => {
                self.options_value = self.field.values.filter((value) =>
                    event.allowed_bind_value_ids.includes(value.id)
                );
            });

            onValueChange();
            bindOptionsDecorators();
        });

        const none_value = getNoneElement(select);
        const options = { locale: document.body.dataset.userLocale, none_value };

        self.destroy = createListPicker(select, options).destroy;

        onValueChange();
    }

    function onValueChange() {
        self.event_dispatcher.dispatch(
            DidChangeListFieldValue(self.field.field_id, self.value_model.bind_value_ids)
        );
    }

    function bindOptionsDecorators() {
        if (self.field.bindings.type === "users") {
            bindUsersAvatars();
        }
        buildColorValueOptionDataset();
    }

    function getNoneElement(select) {
        for (const item of select.options) {
            if (item.value === "100" || item.value === "number:100") {
                return item.value;
            }
        }

        return null;
    }

    function destroy() {
        self.destroy();
    }

    function isFieldValid() {
        return select.checkValidity();
    }

    function bindUsersAvatars() {
        Array.from(select.options).forEach((option) => {
            const option_value_by_angular = option.value;
            const value_id = Number(option_value_by_angular.replace(/^number:(\d+)$/, "$1"));
            self.field.values.some((value_definition) => {
                if (value_definition.id !== value_id) {
                    return false;
                }

                if (value_definition.user_reference) {
                    option.setAttribute(
                        "data-avatar-url",
                        value_definition.user_reference.avatar_url
                    );
                }
                return true;
            });
        });
    }

    function buildColorValueOptionDataset() {
        Array.from(select.options).forEach((option) => {
            const option_value_by_angular = option.value;
            const value_id = Number(option_value_by_angular.replace(/^number:(\d+)$/, "$1"));
            self.field.values.some((value_definition) => {
                if (value_definition.id !== value_id) {
                    return false;
                }

                if (value_definition.value_color) {
                    option.setAttribute("data-color-value", sanitize(value_definition.value_color));
                }
                return true;
            });
        });
    }
}
