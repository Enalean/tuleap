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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
    <div class="test-plan-backlog-item-container">
        <backlog-item-card v-bind:backlog_item="backlog_item" />
        <list-of-test-definitions
            v-if="backlog_item.is_expanded"
            v-bind:backlog_item="backlog_item"
            data-test="async-list-test-defs"
        />
    </div>
</template>
<script setup lang="ts">
import BacklogItemCard from "./BacklogItemCard.vue";
import { defineAsyncComponent, onMounted } from "vue";
import type { BacklogItem } from "../../type";
import { useNamespacedActions } from "vuex-composition-helpers";
import type { BacklogItemActions } from "../../store/backlog-item/backlog-item-actions";

const ListOfTestDefinitions = defineAsyncComponent(
    () =>
        import(
            /* webpackChunkName: "testplan-tests-list" */ "./TestDefinitions/ListOfTestDefinitions.vue"
        ),
);

const props = defineProps<{
    backlog_item: BacklogItem;
}>();

const { loadTestDefinitions } = useNamespacedActions<
    Pick<BacklogItemActions, "loadTestDefinitions">
>("backlog_item", ["loadTestDefinitions"]);

onMounted((): void => {
    if (!props.backlog_item.are_test_definitions_loaded) {
        loadTestDefinitions(props.backlog_item);
    }
});
</script>
