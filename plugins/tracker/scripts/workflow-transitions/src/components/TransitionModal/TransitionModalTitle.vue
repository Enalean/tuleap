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
        <span v-if="!current_transition" key="loading_label">{{
            $gettext("Loading transition")
        }}</span>
        <span v-else-if="is_workflow_advanced" key="transition_label">{{
            configure_transition_from_to_message()
        }}</span>
        <span v-else key="state_label">{{ configure_all_transition_message() }}</span>
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
    methods: {
        configure_transition_from_to_message() {
            let translated = this.$gettext(
                `Configure transition from %{from_state_label} to %{to_state_label}`,
            );
            return this.$gettextInterpolate(translated, {
                from_state_label: this.from_state_label,
                to_state_label: this.to_state_label,
            });
        },
        configure_all_transition_message() {
            let translated = this.$gettext(`Configure all transitions to %{to_state_label}`);
            return this.$gettextInterpolate(translated, {
                to_state_label: this.to_state_label,
            });
        },
    },
};
</script>
