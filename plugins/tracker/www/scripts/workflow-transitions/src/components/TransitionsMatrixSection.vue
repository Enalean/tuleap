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
                    <th v-for="to in all_target_values" v-bind:key="to.id">
                        {{ to.label }}
                    </th>
                </tr>
            </thead>
            <tbody class="tracker-workflow-transition-tbody">
                <tr v-for="from in all_source_values" v-bind:key="from.id">
                    <td class="tracker-workflow-transition-row-label">{{ from.label }}</td>
                    <transition-matrix-content
                        v-for="to in all_target_values"
                        v-bind:key="to.id"
                        v-bind:from="from"
                        v-bind:to="to"
                        v-bind:transition="findTransition(from, to)"
                    />
                </tr>
            </tbody>
        </table>
        <div v-else class="empty-page tracker-workflow-transition-matrix-empty-state">
            <p
                class="empty-page-text tracker-workflow-transition-matrix-empty-state-field-empty"
                v-translate
            >The field on which the transitions are based has no selectable value</p>
            <a class="tlp-button-primary" v-bind:href="configure_field_url">
                <i class="fa fa-cog"></i>
                <span v-translate>Configure it</span>
            </a>
        </div>
    </section>
</template>
<script>
import { mapState } from "vuex";
import TransitionMatrixContent from "./TransitionMatrixContent.vue";

export default {
    name: "TransitionsMatrixSection",

    components: { TransitionMatrixContent },

    computed: {
        ...mapState(["current_tracker"]),

        all_target_values() {
            const all_target_values = this.current_tracker.fields.find(
                field => field.field_id === this.current_tracker.workflow.field_id
            ).values;

            if (all_target_values === null) {
                return [];
            }

            return all_target_values.filter(value => value.is_hidden === false);
        },

        all_source_values() {
            return [
                {
                    label: this.$gettext("(New artifact)"),
                    id: null
                },
                ...this.all_target_values
            ];
        },
        has_field_values() {
            return this.all_target_values.length > 0;
        },
        configure_field_url() {
            const tracker_id = this.current_tracker.id;
            const workflow_field_id = this.current_tracker.workflow.field_id;

            return `/plugins/tracker/?tracker=${tracker_id}&func=admin-formElement-update&formElement=${workflow_field_id}`;
        }
    },

    methods: {
        findTransition(from, to) {
            return this.current_tracker.workflow.transitions.find(
                transition => transition.from_id === from.id && transition.to_id === to.id
            );
        }
    }
};
</script>
