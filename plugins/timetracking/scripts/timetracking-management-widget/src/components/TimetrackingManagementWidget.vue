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
        v-bind:start_date="start_date"
        v-bind:end_date="end_date"
    />
    <widget-query-editor
        v-else
        v-on:set-dates="setDates"
        v-on:close-edit-mode="is_query_being_edited = false"
        v-bind:start_date="start_date"
        v-bind:end_date="end_date"
    />
</template>

<script setup lang="ts">
import WidgetQueryDisplayer from "./WidgetQueryDisplayer.vue";
import WidgetQueryEditor from "./WidgetQueryEditor.vue";
import { ref } from "vue";

const is_query_being_edited = ref(false);

const start_date = ref(new Date().toISOString().split("T")[0]);
const end_date = ref(new Date().toISOString().split("T")[0]);

const setDates = (start: string, end: string): void => {
    start_date.value = start;
    end_date.value = end;
};
</script>
