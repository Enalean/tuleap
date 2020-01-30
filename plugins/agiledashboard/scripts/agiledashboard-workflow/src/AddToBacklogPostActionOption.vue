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
        v-bind:value="'add_to_backlog'"
        v-bind:disabled="!add_to_backlog_information.valid"
        v-bind:title="add_to_backlog_information.title"
        data-test="add-to-backlog"
    >
        {{ add_to_backlog_information.option }}
    </option>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { PostActionInformation } from "./type";

@Component({ name: "AddToBacklogPostActionOption" })
export default class AddToBacklogPostActionOption extends Vue {
    get add_to_backlog_information(): PostActionInformation {
        return {
            valid: false,
            title: this.$gettext("This feature is still under implementation"),
            option: this.$gettext("Add to the Top Backlog")
        };
    }
}
</script>
