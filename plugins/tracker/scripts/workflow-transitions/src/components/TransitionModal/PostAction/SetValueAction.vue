<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <post-action v-bind:post_action="post_action">
        <div class="tracker-workflow-transition-modal-action-details-element tlp-form-element">
            <label v-bind:for="field_id_input_id" class="tlp-label">
                {{ $gettext("Choose a field") }}
                <i class="fa fa-asterisk"></i>
            </label>
            <select
                v-bind:id="field_id_input_id"
                class="tlp-select"
                data-test-type="field"
                data-test="field"
                v-model="post_action_field"
                required
                v-bind:disabled="is_modal_save_running"
            >
                <option v-bind:value="null" disabled>{{ $gettext("Please choose") }}</option>
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
                        v-bind:disabled="field.disabled && field !== post_action_field"
                        v-bind:data-test-type="`field_${field.field_id}`"
                    >
                        {{ field.label }}
                    </option>
                </optgroup>
            </select>
        </div>

        <div class="tracker-workflow-transition-modal-action-details-element tlp-form-element">
            <label v-bind:for="value_input_id" class="tlp-label">
                {{ $gettext("New value") }}
                <i class="fa fa-asterisk"></i>
            </label>
            <component
                v-bind:is="value_input_component"
                v-bind:id="value_input_id"
                v-model="value"
                v-bind:disabled="is_modal_save_running"
            />
        </div>
    </post-action>
</template>
<script>
import { mapState, mapGetters } from "vuex";
import { DATE_FIELD, INT_FIELD, FLOAT_FIELD } from "@tuleap/plugin-tracker-constants";
import { compare } from "../../../support/string.js";
import PostAction from "./PostAction.vue";
import DateInput from "./DateInput.vue";
import FloatInput from "./FloatInput.vue";
import IntInput from "./IntInput.vue";
import PlaceholderInput from "./PlaceholderInput.vue";

export default {
    name: "SetValueAction",
    components: { PostAction, DateInput, FloatInput, IntInput },
    props: {
        post_action: {
            type: Object,
            mandatory: true,
        },
    },
    computed: {
        ...mapState("transitionModal", ["current_transition", "is_modal_save_running"]),
        ...mapGetters("transitionModal", ["set_value_action_fields"]),
        available_fields() {
            // Side effect is prevented with array duplication before sort
            return [...this.set_value_action_fields].sort((field1, field2) =>
                compare(field1.label, field2.label),
            );
        },
        available_fields_by_groups() {
            return [
                {
                    label: this.$gettext("Integers"),
                    type: INT_FIELD,
                },
                {
                    label: this.$gettext("Floats"),
                    type: FLOAT_FIELD,
                },
                {
                    label: this.$gettext("Dates"),
                    type: DATE_FIELD,
                },
            ].map((group) => ({ ...group, fields: this.getAvailableFieldsOfType(group.type) }));
        },
        field_id_input_id() {
            return `post-action-${this.post_action.unique_id}-field-id`;
        },
        value_input_id() {
            return `post-action-${this.post_action.unique_id}-value`;
        },
        value_input_component() {
            if (this.post_action.field_type === DATE_FIELD) {
                return DateInput;
            } else if (this.post_action.field_type === INT_FIELD) {
                return IntInput;
            } else if (this.post_action.field_type === FLOAT_FIELD) {
                return FloatInput;
            }
            return PlaceholderInput;
        },
        post_action_field: {
            get() {
                if (!this.post_action.field_id) {
                    return null;
                }
                const matching_fields = this.available_fields.filter(
                    (field) => field.field_id === this.post_action.field_id,
                );
                if (matching_fields.length === 0) {
                    return null;
                }
                return matching_fields[0];
            },
            set(new_field) {
                this.$store.commit("transitionModal/updateSetValuePostActionField", {
                    post_action: this.post_action,
                    new_field,
                });
            },
        },
        value: {
            get() {
                return this.post_action.value;
            },
            set(value) {
                this.$store.commit("transitionModal/updateSetValuePostActionValue", {
                    post_action: this.post_action,
                    value,
                });
            },
        },
    },
    methods: {
        getAvailableFieldsOfType(type) {
            return this.available_fields.filter((field) => field.type === type);
        },
    },
};
</script>
