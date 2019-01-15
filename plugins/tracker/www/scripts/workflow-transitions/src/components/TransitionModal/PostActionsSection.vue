<!--
  - Copyright (c) Enalean, 2018 - 2019. All Rights Reserved.
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
    <section class="tlp-modal-body-section">
        <h2 class="tlp-modal-subtitle" v-translate>Actions automatically performed</h2>
        <post-action-skeleton
            v-if="is_loading_modal"
            data-test-type="skeleton"
        />
        <template v-else-if="has_actions">
            <post-action
                v-for="action in visible_actions"
                v-bind:key="getActionId(action)"
                data-test-type="action"
            >
                <select
                    slot="action-type"
                    class="tlp-select"
                    v-bind:value="action.type"
                    disabled
                >
                    <option v-bind:value="RUN_JOB_ACTION_TYPE" v-translate>Launch a CI job</option>
                </select>

                <run-job-action
                    slot="body"
                    v-if="action.type === RUN_JOB_ACTION_TYPE"
                    v-bind:action-id="getActionId(action)"
                    v-bind:job-url="action.job_url"
                />
            </post-action>
        </template>
        <empty-post-action
            v-else
            data-test-type="empty-message"
        />
    </section>
</template>
<script>
import EmptyPostAction from "./Empty/EmptyPostAction.vue";
import PostActionSkeleton from "./Skeletons/PostActionSkeleton.vue";
import RunJobAction from "./RunJobAction.vue";
import PostAction from "./PostAction.vue";
import { mapState } from "vuex";

export default {
    name: "PostActionsSection",
    components: { EmptyPostAction, PostActionSkeleton, RunJobAction, PostAction },
    data() {
        return {
            RUN_JOB_ACTION_TYPE: "run_job",
            SET_FIELD_VALUE_ACTION_TYPE: "set_field_value"
        };
    },
    computed: {
        ...mapState("transitionModal", ["is_loading_modal", "actions"]),
        has_actions() {
            return this.visible_actions && this.visible_actions.length > 0;
        },
        visible_actions() {
            if (!this.actions) {
                return null;
            }
            return this.actions.filter(action => action.type === this.RUN_JOB_ACTION_TYPE);
        }
    },
    methods: {
        getActionId(action) {
            if (action.type === this.SET_FIELD_VALUE_ACTION_TYPE) {
                return `${action.type}_${action.field_type}_${action.id}`;
            } else {
                return `${action.type}_${action.id}`;
            }
        }
    }
};
</script>
