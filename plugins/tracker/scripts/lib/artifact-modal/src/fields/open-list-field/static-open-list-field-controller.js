/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import { select2 } from "tlp";
import { remove } from "lodash-es";
import { initOpenListFieldLabelAndSelectIds } from "./open-list-field-ids-initializer";

export default StaticOpenListFieldController;

StaticOpenListFieldController.$inject = ["$element"];

function StaticOpenListFieldController($element) {
    const self = this;
    Object.assign(self, {
        $onInit: init,
        handleStaticValueSelection,
        handleStaticValueUnselection,
        isStaticValueSelected,
        newOpenListStaticValue,
        isRequiredAndEmpty,
        fieldValues,
    });

    function init() {
        self.merged_values = [];

        const open_list_element = $element[0].querySelector(
            ".tuleap-artifact-modal-open-list-static",
        );
        if (!open_list_element) {
            return;
        }

        initOpenListFieldLabelAndSelectIds(
            $element[0],
            open_list_element,
            self.value_model.field_id,
        );

        select2(open_list_element, {
            placeholder: self.field.hint,
            allowClear: true,
            tags: true,
            createTag: self.newOpenListStaticValue,
        });

        $element.on("select2:selecting", self.handleStaticValueSelection);

        $element.on("select2:unselecting", self.handleStaticValueUnselection);
    }

    function fieldValues() {
        if (self.merged_values.length === 0) {
            const union_values = [
                ...self.field.values,
                ...self.value_model.value.bind_value_objects,
            ];
            self.merged_values = [
                ...new Map(
                    union_values.map((union_value) => [parseInt(union_value.id, 10), union_value]),
                ).values(),
            ];
        }
        return self.merged_values;
    }

    function isRequiredAndEmpty() {
        return self.field.required && self.value_model.value.bind_value_objects.length === 0;
    }

    function isStaticValueSelected(field_value) {
        return self.value_model.value.bind_value_objects.some(
            (value_object) => value_object.id === field_value.id.toString(),
        );
    }

    function handleStaticValueSelection(event) {
        var new_selection = event.params.args.data;
        var new_value_model_value = {
            label: new_selection.text,
        };

        if (new_selection.isTag !== true) {
            new_value_model_value.id = new_selection.id;
        }

        self.value_model.value.bind_value_objects.push(new_value_model_value);
    }

    function handleStaticValueUnselection(event) {
        var removed_selection = event.params.args.data;

        remove(self.value_model.value.bind_value_objects, function (value_object) {
            if (removed_selection.isTag === true) {
                return value_object.label === removed_selection.text;
            }
            return value_object.id === removed_selection.id;
        });
    }

    function newOpenListStaticValue(new_open_value) {
        var term = new_open_value.term.trim();

        if (term === "") {
            return null;
        }

        const tag_already_exists = self.field.values.some(
            (value) => value.label.toLowerCase() === term.toLowerCase(),
        );
        if (tag_already_exists === true) {
            return null;
        }

        return {
            id: term,
            text: term,
            isTag: true,
        };
    }
}
