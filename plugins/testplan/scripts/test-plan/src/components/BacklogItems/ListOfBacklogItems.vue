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
        <backlog-item-empty-state v-if="should_empty_state_be_displayed" />
        <backlog-item-error-state v-if="should_error_state_be_displayed" />
    </section>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { namespace } from "vuex-class";
import { BacklogItem } from "../../type";
import ListOfBacklogItemsHeader from "./ListOfBacklogItemsHeader.vue";
import BacklogItemSkeleton from "./BacklogItemSkeleton.vue";
import BacklogItemCard from "./BacklogItemCard.vue";
import BacklogItemContainer from "./BacklogItemContainer.vue";

const backlog_item = namespace("backlog_item");

@Component({
    components: {
        BacklogItemContainer,
        BacklogItemCard,
        BacklogItemSkeleton,
        ListOfBacklogItemsHeader,
        "backlog-item-empty-state": (): Promise<Record<string, unknown>> =>
            import(
                /* webpackChunkName: "testplan-backlog-items-emptystate" */ "./BacklogItemEmptyState.vue"
            ),
        "backlog-item-error-state": (): Promise<Record<string, unknown>> =>
            import(
                /* webpackChunkName: "testplan-backlog-items-errorstate" */ "./BacklogItemErrorState.vue"
            ),
    },
})
export default class ListOfBacklogItems extends Vue {
    @backlog_item.State
    readonly is_loading!: boolean;

    @backlog_item.State
    readonly has_loading_error!: boolean;

    @backlog_item.State
    readonly backlog_items!: BacklogItem[];

    @backlog_item.Action
    readonly loadBacklogItems!: () => Promise<void>;

    created(): void {
        this.loadBacklogItems();
    }

    get should_empty_state_be_displayed(): boolean {
        return this.backlog_items.length === 0 && !this.is_loading && !this.has_loading_error;
    }

    get should_error_state_be_displayed(): boolean {
        return this.has_loading_error;
    }
}
</script>
