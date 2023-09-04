<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <post-action v-bind:post_action="post_action">
        <div
            class="tracker-workflow-transition-modal-action-details-element tlp-form-element"
            data-test="add-to-backlog-post-action-description"
        >
            <p class="tlp-text-info">
                {{ add_to_backlog_agile_dashboard_post_action_detail }}
            </p>
        </div>
    </post-action>
</template>

<script>
import PostAction from "../PostAction/PostAction.vue";
import { mapState } from "vuex";

export default {
    name: "AddToBackLogAgileDashboardPostAction",
    components: { PostAction },
    computed: {
        ...mapState("transitionModal", ["is_split_feature_flag_enabled"]),
        add_to_backlog_agile_dashboard_post_action_detail() {
            return this.is_split_feature_flag_enabled
                ? this.$gettext(
                      "The artifact is added to Scrum backlog (Agile Dashboard). If it's already present, nothing is done.",
                  )
                : this.$gettext(
                      "The artifact is added to Scrum top backlog (Agile Dashboard). If it's already present, nothing is done.",
                  );
        },
    },
    props: {
        post_action: {
            type: Object,
            mandatory: true,
        },
    },
};
</script>
