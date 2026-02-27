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
    <button
        class="tlp-button-primary"
        v-bind:disabled="is_button_disabled"
        v-bind:title="button_title"
        v-on:click="showModal"
        data-test="create-pull-request"
    >
        <i class="fas fa-code-branch fa-rotate-270 tlp-button-icon"></i>
        {{ $gettext("Create pull request") }}
    </button>
</template>

<script>
import { inject } from "vue";
import { CAN_CREATE_PULLREQUEST, HAS_ERROR_WHILE_LOADING_BRANCHES } from "../injection-keys.ts";

export default {
    name: "CreatePullrequestButton",
    props: {
        showModal: Function,
    },
    setup() {
        const can_create_pullrequest = inject(CAN_CREATE_PULLREQUEST);
        const has_error_while_loading_branches = inject(HAS_ERROR_WHILE_LOADING_BRANCHES);

        return {
            can_create_pullrequest,
            has_error_while_loading_branches,
        };
    },
    computed: {
        is_button_disabled() {
            return !this.can_create_pullrequest && !this.has_error_while_loading_branches;
        },
        button_title() {
            return this.is_button_disabled
                ? this.$gettext("No pull request can currently be created")
                : "";
        },
    },
};
</script>
