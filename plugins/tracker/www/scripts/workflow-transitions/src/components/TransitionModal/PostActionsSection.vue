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
    <section class="tlp-modal-body-section tracker-workflow-transition-modal-actions-section">
        <h2 class="tlp-modal-subtitle" v-translate>Actions automatically performed</h2>
        <post-action-skeleton
            v-if="is_loading_modal"
            data-test-type="skeleton"
        />
        <template v-else-if="has_post_actions">
            <post-action
                v-for="post_action in post_actions"
                v-bind:key="post_action.unique_id"
                data-test-type="action"
            >
                <select
                    slot="action-type"
                    class="tlp-select"
                    v-bind:value="post_action.type"
                    disabled
                >
                    <option v-bind:value="RUN_JOB_ACTION_TYPE" v-translate>Launch a CI job</option>
                    <option v-bind:value="SET_FIELD_VALUE_ACTION_TYPE" v-translate>Change the value of a field</option>
                </select>

                <run-job-action
                    slot="body"
                    v-if="post_action.type === RUN_JOB_ACTION_TYPE"
                    v-bind:action-id="post_action.unique_id"
                />
                <set-value-action
                    slot="body"
                    v-else-if="post_action.type === SET_FIELD_VALUE_ACTION_TYPE"
                    v-bind:action-id="post_action.unique_id"
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
import SetValueAction from "./SetValueAction.vue";
import PostAction from "./PostAction.vue";
import { mapState, mapGetters } from "vuex";

export default {
    name: "PostActionsSection",
    components: { EmptyPostAction, PostActionSkeleton, RunJobAction, SetValueAction, PostAction },
    data() {
        return {
            RUN_JOB_ACTION_TYPE: "run_job",
            SET_FIELD_VALUE_ACTION_TYPE: "set_field_value"
        };
    },
    computed: {
        ...mapState("transitionModal", ["is_loading_modal"]),
        ...mapGetters("transitionModal", ["post_actions"]),
        has_post_actions() {
            return this.post_actions && this.post_actions.length > 0;
        }
    }
};
</script>
