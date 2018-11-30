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
        <table v-if="has_field_values" class="tlp-table tracker-workflow-transition-table">
            <thead>
                <tr>
                    <th></th>
                    <th
                        v-for="field_to in all_field_values_to"
                        v-bind:key="field_to.id"
                    >
                        <span class="tracker-workflow-transition-column-label">{{ field_to.label }}</span>
                    </th>
                </tr>
            </thead>
            <tbody class="tracker-workflow-transition-tbody">
                <tr
                    v-for="field_from in all_field_values_from"
                    v-bind:key="field_from.id"
                >
                    <td class="tracker-workflow-transition-row-label">{{ field_from.label }}</td>

                    <td
                        v-for="field_to in all_field_values_to"
                        v-bind:key="field_to.id"
                        v-bind:class="transitionCellClass(field_from.id, field_to.id)"
                    >
                        <button
                            class="tlp-button-primary tlp-button-mini tracker-workflow-advanced-transition-button"
                            v-if="isThereATransition(field_from.id, field_to.id)"
                            v-translate
                            disabled
                        >
                            Configure
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
        <div v-else class="empty-page tracker-workflow-transition-matrix-empty-state">
            <p class="empty-page-text tracker-workflow-transition-matrix-empty-state-field-empty" v-translate>
                The field on which the transitions are based has no selectable value
            </p>
            <a class="tlp-button-primary" v-bind:href="configure_field_url">
                <i class="fa fa-cog"></i>
                <span v-translate>Configure it</span>
            </a>
        </div>
    </section>
</template>
<script>
import { mapState } from "vuex";

export default {
    name: "TransitionsMatrixSection",

    computed: {
        ...mapState(["current_tracker"]),

        all_field_values_to() {
            const all_values = this.current_tracker.fields.find(
                field => field.field_id === this.current_tracker.workflow.field_id
            ).values;

            if (all_values === null) {
                return [];
            }

            return all_values.filter(value => value.is_hidden === false);
        },

        all_field_values_from() {
            return [
                {
                    label: this.$gettext("(New artifact)"),
                    id: null
                },
                ...this.all_field_values_to
            ];
        },
        has_field_values() {
            return this.all_field_values_to.length > 0;
        },
        configure_field_url() {
            const tracker_id = this.current_tracker.id;
            const workflow_field_id = this.current_tracker.workflow.field_id;

            return `/plugins/tracker/?tracker=${tracker_id}&func=admin-formElement-update&formElement=${workflow_field_id}`;
        }
    },

    methods: {
        isThereATransition(from_id, to_id) {
            return this.current_tracker.workflow.transitions.some(transition => {
                return transition.from_id === from_id && transition.to_id === to_id;
            });
        },

        transitionCellClass(from_id, to_id) {
            const class_name = "tracker-workflow-transition-row-content";

            if (from_id === to_id) {
                return `${class_name}-forbidden`;
            }

            if (this.isThereATransition(from_id, to_id)) {
                return `${class_name}-active`;
            }

            return class_name;
        }
    }
};
</script>
