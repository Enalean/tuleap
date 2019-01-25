<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
    <div>
        <div class="tlp-form-element">
            <label
                v-bind:for="field_id_input_id"
                class="tlp-label"
                v-translate
            >
                Choose a field
            </label>
            <select
                v-bind:id="field_id_input_id"
                class="tlp-select"
                data-test-type="field"
                v-model="post_action_field"
                disabled
            >
                <option
                    v-bind:value="null"
                    v-translate
                    disabled
                >
                    Please choose
                </option>
                <optgroup
                    v-for="group in available_fields_by_groups"
                    v-bind:key="group.label"
                    v-bind:label="group.label"
                    v-bind:data-test-type="`${group.type}-group`"
                >
                    <option
                        v-for="field in group.fields"
                        v-bind:key="field.field_id"
                        v-bind:value="field"
                        v-bind:data-test-type="`field_${field.field_id}`"
                    >
                        {{ field.label }}
                    </option>
                </optgroup>
            </select>
        </div>

        <div class="tlp-form-element">
            <label
                v-bind:for="value_input_id"
                class="tlp-label"
                v-translate
            >
                New value
            </label>
            <select
                v-if="is_date_field"
                v-bind:id="value_input_id"
                class="tlp-select"
                data-test-type="date-value"
                v-model="value"
                disabled
            >
                <option
                    v-bind:value="null"
                    v-translate
                    disabled
                >
                    Please choose
                </option>
                <option
                    v-bind:value="DATE_FIELD_VALUE.CLEAR"
                    v-translate
                >
                    Clear
                </option>
                <option
                    v-bind:value="DATE_FIELD_VALUE.CURRENT"
                    v-translate
                >
                    Current time
                </option>
            </select>
            <input
                v-else-if="is_int_field"
                v-bind:id="value_input_id"
                type="number"
                step="1"
                class="tlp-input"
                data-test-type="int-value"
                v-bind:value="value"
                disabled
            >
            <input
                v-else-if="is_float_field"
                v-bind:id="value_input_id"
                type="number"
                step="any"
                class="tlp-input"
                data-test-type="float-value"
                v-bind:value="value"
                disabled
            >
        </div>
    </div>
</template>

<script>
import { DATE_FIELD, INT_FIELD, FLOAT_FIELD } from "../../../../constants/fields-constants.js";
import { DATE_FIELD_VALUE } from "../../constants/workflow-constants.js";

import { compare } from "../../support/string.js";
import { mapState } from "vuex";

export default {
    name: "SetValueAction",
    props: {
        actionId: {
            type: String,
            mandatory: true
        }
    },
    data() {
        return {
            DATE_FIELD_VALUE
        };
    },
    computed: {
        ...mapState(["current_tracker"]),
        ...mapState("transitionModal", [
            "current_transition",
            "post_actions_by_unique_id",
            "is_modal_save_running"
        ]),
        available_fields() {
            // Side effect is prevented with array duplication before sort
            return [...this.current_tracker.fields].sort((field1, field2) =>
                compare(field1.label, field2.label)
            );
        },
        available_fields_by_groups() {
            return [
                {
                    label: this.$gettext("Integers"),
                    type: INT_FIELD
                },
                {
                    label: this.$gettext("Floats"),
                    type: FLOAT_FIELD
                },
                {
                    label: this.$gettext("Dates"),
                    type: DATE_FIELD
                }
            ].map(group => ({ ...group, fields: this.available_fields_of_type(group.type) }));
        },
        post_action() {
            return this.post_actions_by_unique_id[this.actionId];
        },
        field_id_input_id() {
            return `post-action-${this.actionId}-field-id`;
        },
        value_input_id() {
            return `post-action-${this.actionId}-value`;
        },
        post_action_field() {
            if (!this.post_action.field_id) {
                return null;
            }
            const matching_fields = this.available_fields.filter(
                field => field.field_id === this.post_action.field_id
            );
            if (matching_fields.length === 0) {
                return null;
            }
            return matching_fields[0];
        },
        is_date_field() {
            return this.post_action.field_type === DATE_FIELD;
        },
        is_int_field() {
            return this.post_action.field_type === INT_FIELD;
        },
        is_float_field() {
            return this.post_action.field_type === FLOAT_FIELD;
        },
        value() {
            return this.post_action.value;
        }
    },
    methods: {
        available_fields_of_type(type) {
            return this.available_fields.filter(field => field.type === type);
        }
    }
};
</script>
