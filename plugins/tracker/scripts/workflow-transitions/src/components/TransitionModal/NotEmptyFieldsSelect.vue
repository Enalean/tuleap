<!--
  - Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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
  -->
<template>
    <div class="tlp-form-element" data-test="not-empty-field-form-element">
        <label for="workflow-configuration-not-empty-fields" class="tlp-label">
            {{ $gettext("Field(s) that must not be empty") }}
        </label>

        <select
            id="workflow-configuration-not-empty-fields"
            multiple
            v-on:change="updateNotEmptyFields"
            v-bind:disabled="is_modal_save_running"
            data-test="not-empty-field-select"
            ref="workflow_configuration_not_empty_fields"
        >
            <option
                v-for="field in writable_fields"
                v-bind:key="field.field_id"
                v-bind:value="field.field_id"
                v-bind:selected="not_empty_field_ids.includes(field.field_id)"
            >
                {{ field.label }}
            </option>
        </select>
    </div>
</template>

<script lang="js">
import { defineComponent } from "vue";
import { mapState } from "vuex";
import { createListPicker } from "@tuleap/list-picker";
import { compare } from "../../support/string.js";
import {
    COMPUTED_FIELD,
    READ_ONLY_FIELDS,
    STRUCTURAL_FIELDS,
} from "@tuleap/plugin-tracker-constants";

const fields_blacklist = [...STRUCTURAL_FIELDS, ...READ_ONLY_FIELDS, COMPUTED_FIELD];
export default defineComponent({
    name: "NotEmptyFieldsSelect",
    data() {
        return {
            not_empty_fields_list_picker: null,
        };
    },
    computed: {
        ...mapState("transitionModal", ["current_transition", "is_modal_save_running"]),
        ...mapState({
            writable_fields: (state) => {
                if (state.current_tracker === null) {
                    return [];
                }
                return state.current_tracker.fields
                    .filter((field) => !fields_blacklist.includes(field.type))
                    .sort((field1, field2) => compare(field1.label, field2.label));
            },
        }),
        not_empty_field_ids() {
            if (this.current_transition) {
                return this.current_transition.not_empty_field_ids;
            }

            return [];
        },
    },
    mounted() {
        this.not_empty_fields_list_picker = createListPicker(
            this.$refs.workflow_configuration_not_empty_fields,
            {
                locale: document.body.dataset.userLocale,
                is_filterable: true,
                placeholder: this.$gettext("Choose a field"),
            },
        );
    },
    beforeUnmount() {
        this.not_empty_fields_list_picker.destroy();
    },
    methods: {
        updateNotEmptyFields(event) {
            const select = event.target;
            const values = Array.from(select.options)
                .filter((option) => option.selected)
                .map((option) => Number.parseInt(option.value, 10));

            this.$store.commit("transitionModal/updateNotEmptyFieldIds", values);
        },
    },
});
</script>
