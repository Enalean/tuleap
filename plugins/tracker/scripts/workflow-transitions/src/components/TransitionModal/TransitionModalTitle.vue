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
  -->
<template>
    <h1 class="tlp-modal-title" id="configure-modal-title">
        <translate v-if="!current_transition" key="loading_label">Loading transition</translate>
        <translate
            v-else-if="is_workflow_advanced"
            key="transition_label"
            v-bind:translate-params="{
                from_state_label,
                to_state_label,
            }"
        >
            Configure transition from %{from_state_label} to %{to_state_label}
        </translate>
        <translate v-else key="state_label" v-bind:translate-params="{ to_state_label }">
            Configure all transitions to %{to_state_label}
        </translate>
    </h1>
</template>
<script>
import { mapGetters, mapState } from "vuex";

export default {
    name: "TransitionModalTitle",
    computed: {
        ...mapGetters(["all_target_states", "is_workflow_advanced"]),
        ...mapState("transitionModal", ["current_transition"]),
        ...mapState("transitionModal", {
            from_state_label(state) {
                if (state.current_transition === null) {
                    return null;
                }
                if (state.current_transition.from_id === null) {
                    return this.$gettext("(New artifact)");
                }
                return this.all_target_states.find(
                    (from_state) => from_state.id === state.current_transition.from_id,
                ).label;
            },
            to_state_label(state) {
                if (state.current_transition === null) {
                    return null;
                }
                return this.all_target_states.find(
                    (to_state) => to_state.id === state.current_transition.to_id,
                ).label;
            },
        }),
    },
};
</script>
