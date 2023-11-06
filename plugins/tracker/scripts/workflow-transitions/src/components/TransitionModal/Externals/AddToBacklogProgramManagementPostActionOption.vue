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
    <option
        v-if="is_program_management_used"
        v-bind:value="'program_management_add_to_top_backlog'"
        v-bind:disabled="!add_to_backlog_information.valid"
        v-bind:title="add_to_backlog_information.title"
        data-test="add-to-backlog-program-management"
    >
        {{ add_to_backlog_information.option }}
    </option>
</template>

<script>
import { mapGetters } from "vuex";
import { EXTERNAL_POST_ACTION_TYPE } from "../../../constants/workflow-constants.js";

export default {
    name: "AddToBacklogProgramManagementPostActionOption",
    props: {
        post_action_type: String,
    },
    computed: {
        ...mapGetters("transitionModal", ["is_program_management_used", "post_actions"]),
        add_to_backlog_information() {
            if (
                this.add_to_top_backlog_is_already_present &&
                this.post_action_type !==
                    EXTERNAL_POST_ACTION_TYPE.ADD_TO_BACKLOG_PROGRAM_MANAGEMENT
            ) {
                return {
                    valid: false,
                    option: this.$gettext("Add to the backlog (already used)"),
                    title: this.$gettext("You can only have this post-action once."),
                };
            }
            return {
                valid: true,
                option: this.$gettext("Add to the backlog"),
                title: "",
            };
        },
        add_to_top_backlog_is_already_present() {
            return (
                this.post_actions.filter(
                    (post_action) =>
                        post_action.type ===
                        EXTERNAL_POST_ACTION_TYPE.ADD_TO_BACKLOG_PROGRAM_MANAGEMENT,
                ).length > 0
            );
        },
    },
};
</script>
