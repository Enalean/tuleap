<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <section class="tlp-pane-section" data-test="tracker-workflow-matrix">
        <table v-if="has_field_values" class="tlp-table tracker-workflow-transition-table">
            <thead>
                <tr>
                    <th></th>
                    <transition-matrix-column-header
                        v-for="to in all_target_states"
                        v-bind:key="to.id"
                        v-bind:column="to"
                    />
                </tr>
            </thead>
            <tbody class="tracker-workflow-transition-tbody">
                <tr v-for="from in all_source_values" v-bind:key="from.id" data-test="matrix-row">
                    <td class="tracker-workflow-transition-row-label">
                        {{ from.label }}
                    </td>
                    <transition-matrix-content
                        v-for="to in all_target_states"
                        v-bind:key="to.id"
                        v-bind:from="from"
                        v-bind:to="to"
                    />
                </tr>
            </tbody>
        </table>
        <section v-else class="empty-state-pane">
            <p class="empty-state-text">
                {{
                    $gettext("The field on which the transitions are based has no selectable value")
                }}
            </p>
            <a class="empty-state-action tlp-button-primary" v-bind:href="configure_field_url">
                <i class="fa fa-cog tlp-button-icon"></i>
                <span>{{ $gettext("Configure it") }}</span>
            </a>
        </section>
    </section>
</template>
<script>
import { mapGetters, mapState } from "vuex";
import TransitionMatrixContent from "./TransitionMatrixContent.vue";
import TransitionMatrixColumnHeader from "./TransitionMatrixColumnHeader.vue";

export default {
    name: "TransitionsMatrixSection",
    components: { TransitionMatrixColumnHeader, TransitionMatrixContent },
    computed: {
        ...mapState(["current_tracker"]),
        ...mapGetters(["all_target_states"]),

        all_source_values() {
            return [
                {
                    label: this.$gettext("(New artifact)"),
                    id: null,
                },
                ...this.all_target_states,
            ];
        },
        has_field_values() {
            return this.all_target_states.length > 0;
        },
        configure_field_url() {
            const tracker_id = this.current_tracker.id;
            const workflow_field_id = this.current_tracker.workflow.field_id;

            return `/plugins/tracker/?tracker=${tracker_id}&func=admin-formElement-update&formElement=${workflow_field_id}`;
        },
    },
};
</script>
