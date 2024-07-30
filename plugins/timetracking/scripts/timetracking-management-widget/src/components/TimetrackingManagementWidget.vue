<!--
  - Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
    <widget-query-displayer
        v-if="!is_query_being_edited"
        v-on:click="is_query_being_edited = true"
    />
    <widget-query-editor v-else v-on:close-edit-mode="is_query_being_edited = false" />
    <widget-query-save-request v-show="!is_query_being_edited && has_the_query_been_modified" />
</template>

<script setup lang="ts">
import WidgetQueryDisplayer from "./WidgetQueryDisplayer.vue";
import WidgetQueryEditor from "./WidgetQueryEditor.vue";
import { ref } from "vue";
import WidgetQuerySaveRequest from "./WidgetQuerySaveRequest.vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { RETRIEVE_QUERY } from "../injection-symbols";

const is_query_being_edited = ref(false);
const { has_the_query_been_modified } = strictInject(RETRIEVE_QUERY);
</script>
