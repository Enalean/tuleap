<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div class="taskboard-swimlane" data-navigation="swimlane" tabindex="0">
        <parent-cell v-bind:swimlane="swimlane" />
        <drop-container-cell
            v-for="col of columns"
            v-bind:key="col.id"
            v-bind:column="col"
            v-bind:swimlane="swimlane"
            v-bind:is_solo_card="false"
        />
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { ColumnDefinition, Swimlane } from "../../../../type";
import ParentCell from "./ParentCell.vue";
import { useStore } from "vuex-composition-helpers";
import DropContainerCell from "./Cell/DropContainerCell.vue";

defineProps<{
    swimlane: Swimlane;
}>();

const store = useStore();

const columns = computed((): ColumnDefinition[] => {
    return store.state.column.columns;
});
</script>
