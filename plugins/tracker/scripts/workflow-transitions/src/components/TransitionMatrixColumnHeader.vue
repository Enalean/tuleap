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
  -->

<template>
    <th>
        <div>{{ column.label }}</div>
        <configure-state-button
            v-if="!is_workflow_advanced && first_transition"
            v-bind:transition="first_transition"
        />
    </th>
</template>
<script>
import { mapGetters } from "vuex";
import ConfigureStateButton from "./ConfigureStateButton.vue";
export default {
    name: "TransitionMatrixColumnHeader",
    components: { ConfigureStateButton },
    props: {
        column: {
            type: Object,
            required: true,
        },
    },
    computed: {
        ...mapGetters(["is_workflow_advanced", "current_workflow_transitions"]),
        first_transition() {
            // We never choose the transition from new unless it's the only one.
            // This is because the "comment not empty" transition is not taken into account on it.
            let transition_from_new = null;
            for (const transition of this.current_workflow_transitions) {
                if (transition.to_id !== this.column.id) {
                    continue;
                }
                if (transition.from_id === null) {
                    transition_from_new = transition;
                } else {
                    return transition;
                }
            }
            return transition_from_new;
        },
    },
};
</script>
