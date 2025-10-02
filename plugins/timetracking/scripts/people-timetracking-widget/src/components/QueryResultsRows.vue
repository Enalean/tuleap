<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <tbody data-test="all-rows">
        <query-results-row
            v-for="user_times in results"
            v-bind:key="user_times.user.id"
            v-bind:user_times="user_times"
        />
    </tbody>
</template>

<script setup lang="ts">
import type { QueryResults } from "../type";
import QueryResultsRow from "./QueryResultsRow.vue";

defineProps<{
    results: QueryResults;
}>();
</script>
