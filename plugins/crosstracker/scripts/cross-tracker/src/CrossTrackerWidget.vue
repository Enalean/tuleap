<!--
  - Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
    <read-query
        v-if="widget_pane === 'query-active'"
        v-on:switch-to-create-query-pane="handleCreateNewQuery"
    />
    <create-new-query
        v-else-if="widget_pane === 'query-creation' && is_multiple_query_supported && is_user_admin"
        v-on:return-to-active-query-pane="displayActiveQuery"
    />
</template>
<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { EMITTER, IS_MULTIPLE_QUERY_SUPPORTED, IS_USER_ADMIN } from "./injection-symbols";
import { CREATE_NEW_QUERY } from "./helpers/emitter-provider";
import CreateNewQuery from "./components/query/creation/CreateNewQuery.vue";
import { QUERY_ACTIVE_PANE, QUERY_CREATION_PANE } from "./domain/WidgetPaneDisplay";
import ReadQuery from "./components/ReadQuery.vue";

const is_user_admin = strictInject(IS_USER_ADMIN);
const emitter = strictInject(EMITTER);
const is_multiple_query_supported = strictInject(IS_MULTIPLE_QUERY_SUPPORTED);

const widget_pane = ref(QUERY_ACTIVE_PANE);

function displayActiveQuery(): void {
    widget_pane.value = QUERY_ACTIVE_PANE;
}

onMounted(() => {
    emitter.on(CREATE_NEW_QUERY, handleCreateNewQuery);
});

onBeforeUnmount(() => {
    emitter.off(CREATE_NEW_QUERY);
});

function handleCreateNewQuery(): void {
    widget_pane.value = QUERY_CREATION_PANE;
}
</script>
