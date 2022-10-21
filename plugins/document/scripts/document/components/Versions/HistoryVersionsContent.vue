<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
  -
  -->

<template>
    <tbody>
        <history-versions-content-row
            v-for="version in versions"
            v-bind:key="version.id"
            v-bind:item="item"
            v-bind:version="version"
            v-bind:has_more_than_one_version="versions.length > 1"
            v-bind:location="location"
        />
    </tbody>
</template>

<script setup lang="ts">
import type { FileHistory, Item } from "../../type";
import HistoryVersionsContentRow from "./HistoryVersionsContentRow.vue";
import { ref } from "vue";

defineProps<{ item: Item; versions: readonly FileHistory[] }>();

const location = ref(window.location);
</script>
