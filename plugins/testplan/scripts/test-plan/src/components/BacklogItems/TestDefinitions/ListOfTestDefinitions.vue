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
    <div class="test-plan-list-of-test-definitions">
        <test-definition
            v-for="test_definition of backlog_item.test_definitions"
            v-bind:key="test_definition.id"
            v-bind:test_definition="test_definition"
        />
        <test-definition-skeleton v-if="backlog_item.is_loading_test_definitions" />
        <test-definition-empty-state v-if="should_empty_state_be_displayed" />
        <test-definition-error-state v-if="should_error_state_be_displayed" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import TestDefinitionSkeleton from "./TestDefinitionSkeleton.vue";
import { BacklogItem } from "../../../type";
import { namespace } from "vuex-class";
import TestDefinitionCard from "./TestDefinitionCard.vue";
import TestDefinitionEmptyState from "./TestDefinitionEmptyState.vue";
import TestDefinitionErrorState from "./TestDefinitionErrorState.vue";

const backlog_item_store = namespace("backlog_item");

@Component({
    components: {
        TestDefinitionErrorState,
        TestDefinitionEmptyState,
        TestDefinition: TestDefinitionCard,
        TestDefinitionSkeleton,
    },
})
export default class ListOfTestDefinitions extends Vue {
    @Prop({ required: true })
    readonly backlog_item!: BacklogItem;

    @backlog_item_store.Action
    readonly loadTestDefinitions!: (backlog_item: BacklogItem) => Promise<void>;

    mounted(): void {
        if (!this.backlog_item.are_test_definitions_loaded) {
            this.loadTestDefinitions(this.backlog_item);
        }
    }

    get should_empty_state_be_displayed(): boolean {
        return (
            this.backlog_item.test_definitions.length === 0 &&
            !this.backlog_item.is_loading_test_definitions &&
            !this.backlog_item.has_test_definitions_loading_error
        );
    }

    get should_error_state_be_displayed(): boolean {
        return this.backlog_item.has_test_definitions_loading_error;
    }
}
</script>
