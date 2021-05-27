/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

const template = `<div class="tlp-form-element" data-test="radiobutton-field">
    <label class="tlp-label">
        {{ $ctrl.field.label }}
        <i ng-if="::$ctrl.field.required" class="fa fa-asterisk"></i>
    </label>

    <label for="{{ ::('rb_' + $ctrl.field.field_id + '_100') }}"
        class="tlp-label tlp-radio"
        ng-if="! $ctrl.field.required"
        data-test="radiobutton-field-value"
    >
        <input type="radio"
            ng-model="$ctrl.value_model.bind_value_ids[0]"
            name="{{ ::$ctrl.field.name }}"
            ng-value="100"
            ng-required="{{ ::$ctrl.field.required }}"
            ng-disabled="::$ctrl.isDisabled()"
            id="{{ ::('rb_' + $ctrl.field.field_id + '_100') }}"
            data-test="radiobutton-field-input"
        >
        <span translate>None</span>
    </label>

    <label ng-repeat="field_value in $ctrl.field.values"
        for="{{ ::('rb_' + $ctrl.field.field_id + '_' + field_value.id) }}"
        class="tlp-label tlp-radio"
        data-test="radiobutton-field-value"
    >
        <input type="radio"
            ng-model="$ctrl.value_model.bind_value_ids[0]"
            name="{{ ::$ctrl.field.name }}"
            ng-value="::$ctrl.castValue(field_value.id)"
            ng-required="{{ ::$ctrl.field.required }}"
            ng-disabled="::$ctrl.isDisabled()"
            id="{{ ::('rb_' + $ctrl.field.field_id + '_' + field_value.id) }}"
            data-test="radiobutton-field-input"
        >
        {{ field_value.label }}
    </label>
</div>`;

function controller() {
    const self = this;
    Object.assign(self, {
        // Cast to string because ugroups can look like 101_3
        castValue: (value) =>
            self.field.bindings.type === "ugroups" ? String(value) : Number(value),
    });
}

export default {
    template,
    controller,
    bindings: {
        field: "<",
        value_model: "<valueModel",
        isDisabled: "&",
    },
};
