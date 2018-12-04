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
    <td class="tracker-workflow-transition-cell">
        <i
            v-if="!is_allowed"
            class="fa fa-ban tracker-workflow-transition-cell-forbidden"
            data-test-type="forbidden-transition"
        ></i>
        <i
            v-else-if="is_operation_running"
            class="fa fa-spinner fa-spin tracker-workflow-transition-spinner"
            data-test-type="spinner"
        ></i>
        <div
            v-else-if="is_empty"
            class="tracker-workflow-transition-cell-empty"
            v-bind:class="{ 'tracker-workflow-transition-action-disabled': is_another_operation_running }"
            v-on:click="is_another_operation_running || createTransition()"
            data-test-action="create-transition"
        ></div>
        <template v-else-if="transition">
            <div
                class="tracker-workflow-transition-mark"
                v-bind:class="{ 'tracker-workflow-transition-action-disabled': is_another_operation_running }"
                data-test-action="delete-transition"
                v-on:click="is_another_operation_running || deleteTransition()"
            >
                ⤴
            </div>
            <button
                class="tlp-button-primary tlp-button-mini tracker-workflow-advanced-transition-button"
                data-test-action="configure-transition"
                v-translate
                disabled
            >Configure</button>
        </template>
    </td>
</template>

<script>
import { mapState } from "vuex";

export default {
    name: "TransitionMatrixContent",

    props: {
        from: {
            type: Object,
            mandatory: true
        },
        to: {
            type: Object,
            mandatory: true
        },
        transition: {
            type: Object
        }
    },

    data() {
        return {
            is_operation_running: false
        };
    },

    computed: {
        ...mapState({
            is_another_operation_running: "is_operation_running"
        }),
        is_allowed() {
            return this.from.id !== this.to.id;
        },
        is_empty() {
            return this.is_allowed && !this.transition;
        }
    },

    methods: {
        async createTransition() {
            this.is_operation_running = true;
            try {
                const new_transition = {
                    from_id: this.from.id,
                    to_id: this.to.id
                };
                await this.$store.dispatch("createTransition", new_transition);
            } finally {
                this.is_operation_running = false;
            }
        },

        async deleteTransition() {
            this.is_operation_running = true;
            try {
                await this.$store.dispatch("deleteTransition", this.transition);
            } finally {
                this.is_operation_running = false;
            }
        }
    }
};
</script>
