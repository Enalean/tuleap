<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <section class="tlp-pane-section">
        <p v-translate>In order to configure transitions rules on this tracker, your first need to choose a list field. Once chosen, you will be able to configure transition using the configuration matrix.</p>

        <div class="tlp-form-element">
            <label for="workflow-field" class="tlp-label">
                <span v-translate>Field</span>
                <span
                    class="tlp-tooltip tlp-tooltip-top"
                    v-bind:data-tlp-tooltip="field_tooltip"
                >
                    <i class="fa fa-question-circle"></i>
                </span>
            </label>
            <select
                id="workflow-field"
                class="tlp-select tlp-select-adjusted"
                name="field"
                v-model="selected_field"
            >
                <option value="" disabled></option>
                <option
                    v-for="field in all_fields"
                    v-bind:key="field.id"
                    v-bind:value="field"
                >
                    {{ field.label }}
                </option>
            </select>
        </div>
    </section>
</template>

<script>
import { mapState } from "vuex";

export default {
    name: "FirstConfigurationSection",

    data() {
        return {
            selected_field: null
        };
    },

    computed: {
        ...mapState(["current_tracker"]),
        all_fields() {
            return this.current_tracker.fields
                .filter(field => field.type === "sb" || field.type === "rb")
                .map(field => ({ id: field.field_id, label: field.label }));
        },
        field_tooltip() {
            return this.$gettext("Transitions based field");
        }
    }
};
</script>
