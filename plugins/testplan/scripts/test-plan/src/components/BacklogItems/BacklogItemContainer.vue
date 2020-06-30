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
        />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import BacklogItemCard from "./BacklogItemCard.vue";
import { BacklogItem } from "../../type";
import { namespace } from "vuex-class";

const backlog_item_store = namespace("backlog_item");

@Component({
    components: {
        BacklogItemCard,
        "list-of-test-definitions": (): Promise<Record<string, unknown>> =>
            import(
                /* webpackChunkName: "testplan-tests-list" */ "./TestDefinitions/ListOfTestDefinitions.vue"
            ),
    },
})
export default class BacklogItemContainer extends Vue {
    @Prop({ required: true })
    readonly backlog_item!: BacklogItem;

    @backlog_item_store.Action
    readonly loadTestDefinitions!: (backlog_item: BacklogItem) => Promise<void>;

    mounted(): void {
        if (!this.backlog_item.are_test_definitions_loaded) {
            this.loadTestDefinitions(this.backlog_item);
        }
    }
}
</script>
