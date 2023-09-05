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
    <section class="test-plan-list-of-backlogitems">
        <list-of-backlog-items-header />
        <backlog-item-container
            v-for="backlog_item of backlog_items"
            v-bind:key="backlog_item.id"
            v-bind:backlog_item="backlog_item"
        />
        <backlog-item-skeleton v-if="is_loading" />
        <backlog-item-empty-state
            v-if="should_empty_state_be_displayed"
            data-test="async-empty-state"
        />
        <backlog-item-error-state
            v-if="should_error_state_be_displayed"
            data-test="async-error-state"
        />
    </section>
</template>
<script setup lang="ts">
import ListOfBacklogItemsHeader from "./ListOfBacklogItemsHeader.vue";
import BacklogItemSkeleton from "./BacklogItemSkeleton.vue";
import BacklogItemContainer from "./BacklogItemContainer.vue";
import { computed, defineAsyncComponent } from "vue";
import { useNamespacedActions, useNamespacedState } from "vuex-composition-helpers";
import type { BacklogItemState } from "../../store/backlog-item/type";
import type { BacklogItemActions } from "../../store/backlog-item/backlog-item-actions";

const BacklogItemEmptyState = defineAsyncComponent(
    () =>
        import(
            /* webpackChunkName: "testplan-backlog-items-emptystate" */ "./BacklogItemEmptyState.vue"
        ),
);
const BacklogItemErrorState = defineAsyncComponent(
    () =>
        import(
            /* webpackChunkName: "testplan-backlog-items-errorstate" */ "./BacklogItemErrorState.vue"
        ),
);

const { is_loading, has_loading_error, backlog_items } = useNamespacedState<
    Pick<BacklogItemState, "is_loading" | "has_loading_error" | "backlog_items">
>("backlog_item", ["is_loading", "has_loading_error", "backlog_items"]);

const { loadBacklogItems } = useNamespacedActions<Pick<BacklogItemActions, "loadBacklogItems">>(
    "backlog_item",
    ["loadBacklogItems"],
);

loadBacklogItems();

const should_empty_state_be_displayed = computed((): boolean => {
    return backlog_items.value.length === 0 && !is_loading.value && !has_loading_error.value;
});

const should_error_state_be_displayed = computed((): boolean => {
    return has_loading_error.value;
});
</script>
