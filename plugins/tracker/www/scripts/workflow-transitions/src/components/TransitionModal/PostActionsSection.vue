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
                v-bind:post-action="post_action"
            />
            <button
                class="tlp-button-primary tlp-button-outline tlp-button-small"
                type="button"
                v-on:click="addNewPostAction()"
                v-bind:disabled="is_modal_save_running"
            >
                <i class="fa fa-plus tlp-button-icon"></i>
                <translate>Add another action</translate>
            </button>
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
import PostAction from "./PostAction.vue";
import { mapState, mapGetters, mapMutations } from "vuex";

export default {
    name: "PostActionsSection",
    components: { EmptyPostAction, PostActionSkeleton, PostAction },
    computed: {
        ...mapState("transitionModal", ["is_loading_modal", "is_modal_save_running"]),
        ...mapGetters("transitionModal", ["post_actions"]),
        has_post_actions() {
            return this.post_actions && this.post_actions.length > 0;
        }
    },
    methods: {
        ...mapMutations({
            addNewPostAction: "transitionModal/addPostAction"
        })
    }
};
</script>
