<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
  -
  -->
<template>
    <div v-if="is_created_from_jira && from_jira_data.credentials">
        <input
            type="hidden"
            name="jira_server"
            data-test="jira-server"
            v-bind:value="from_jira_data.credentials.server_url"
        />
        <input
            type="hidden"
            name="jira_user"
            v-bind:value="from_jira_data.credentials.user_email"
        />
        <input type="hidden" name="jira_token" v-bind:value="from_jira_data.credentials.token" />
        <input
            type="hidden"
            name="jira_project"
            v-bind:value="JSON.stringify(from_jira_data.project)"
        />
        <input
            type="hidden"
            name="jira_tracker_name"
            v-bind:value="JSON.stringify(from_jira_data.tracker)"
        />
    </div>
</template>
<script setup lang="ts">
import { useGetters, useState } from "vuex-composition-helpers";

const { from_jira_data } = useState(["from_jira_data"]);
const { is_created_from_jira } = useGetters(["is_created_from_jira"]);
</script>
