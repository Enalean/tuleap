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
    <section class="tlp-modal-body-section tracker-workflow-transition-modal-actions-section">
        <h2 class="tlp-modal-subtitle" v-translate>Actions automatically performed</h2>
        <post-action-skeleton v-if="is_loading_modal" data-test-type="skeleton" />
        <template v-else-if="has_post_actions">
            <component
                v-for="post_action in post_actions"
                v-bind:key="post_action.unique_id"
                v-bind:post_action="post_action"
                v-bind:is="getComponent(post_action)"
                data-test-type="post-action"
            />
            <button
                class="tlp-button-primary tlp-button-outline tlp-button-small"
                type="button"
                v-on:click="addNewPostAction()"
                v-bind:disabled="is_modal_save_running"
                data-test="add-post-action"
            >
                <i class="fa fa-plus tlp-button-icon"></i>
                <translate>Add another action</translate>
            </button>
        </template>
        <empty-post-action v-else data-test-type="empty-message" />
    </section>
</template>
<script>
import { EXTERNAL_POST_ACTION_TYPE, POST_ACTION_TYPE } from "../../constants/workflow-constants.js";
import EmptyPostAction from "./Empty/EmptyPostAction.vue";
import PostActionSkeleton from "./Skeletons/PostActionSkeleton.vue";
import RunJobAction from "./PostAction/RunJobAction.vue";
import SetValueAction from "./PostAction/SetValueAction.vue";
import FrozenFieldsAction from "./PostAction/FrozenFieldsAction.vue";
import HiddenFieldsetsAction from "./PostAction/HiddenFieldsetsAction.vue";
import { mapState, mapGetters, mapMutations } from "vuex";
import AddToBacklogAgileDashboardPostAction from "./Externals/AddToBacklogAgileDashboardPostAction.vue";
import AddToBacklogProgramManagementPostAction from "./Externals/AddToBacklogProgramManagementPostAction.vue";

export default {
    name: "PostActionsSection",
    components: {
        EmptyPostAction,
        PostActionSkeleton,
        RunJobAction,
        SetValueAction,
        FrozenFieldsAction,
        HiddenFieldsetsAction,
    },
    computed: {
        ...mapState("transitionModal", ["is_loading_modal", "is_modal_save_running"]),
        ...mapGetters("transitionModal", ["post_actions"]),
        has_post_actions() {
            return this.post_actions && this.post_actions.length > 0;
        },
    },
    methods: {
        ...mapMutations({
            addNewPostAction: "transitionModal/addPostAction",
        }),
        getComponent(post_action) {
            if (post_action.type === POST_ACTION_TYPE.RUN_JOB) {
                return RunJobAction;
            } else if (post_action.type === POST_ACTION_TYPE.SET_FIELD_VALUE) {
                return SetValueAction;
            } else if (post_action.type === POST_ACTION_TYPE.FROZEN_FIELDS) {
                return FrozenFieldsAction;
            } else if (post_action.type === POST_ACTION_TYPE.HIDDEN_FIELDSETS) {
                return HiddenFieldsetsAction;
            } else if (
                post_action.type === EXTERNAL_POST_ACTION_TYPE.ADD_TO_BACKLOG_AGILE_DASHBOARD
            ) {
                return AddToBacklogAgileDashboardPostAction;
            } else if (
                post_action.type === EXTERNAL_POST_ACTION_TYPE.ADD_TO_BACKLOG_PROGRAM_MANAGEMENT
            ) {
                return AddToBacklogProgramManagementPostAction;
            }

            return null;
        },
    },
};
</script>
