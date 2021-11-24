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
    <div class="test-plan-test-definition-card" v-bind:class="classname">
        <test-definition-card-xref-category-status
            v-bind:test_definition="test_definition"
            v-bind:backlog_item="backlog_item"
        />
        <p class="test-plan-test-definition-title">{{ test_definition.summary }}</p>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { BacklogItem, TestDefinition } from "../../../type";
import type { RemoveIsJustRefreshedFlagOnTestDefinitionPayload } from "../../../store/backlog-item/type";
import { namespace } from "vuex-class";
import TestDefinitionCardXrefCategoryStatus from "./TestDefinitionCardXrefCategoryStatus.vue";

const backlog_item_store = namespace("backlog_item");
@Component({
    components: { TestDefinitionCardXrefCategoryStatus },
})
export default class TestDefinitionCard extends Vue {
    @Prop({ required: true })
    readonly test_definition!: TestDefinition;

    @Prop({ required: true })
    readonly backlog_item!: BacklogItem;

    @backlog_item_store.Mutation
    readonly removeIsJustRefreshedFlagOnTestDefinition!: (
        payload: RemoveIsJustRefreshedFlagOnTestDefinitionPayload
    ) => void;

    mounted(): void {
        if (this.test_definition.is_just_refreshed) {
            setTimeout(() => {
                this.removeIsJustRefreshedFlagOnTestDefinition({
                    backlog_item: this.backlog_item,
                    test_definition: this.test_definition,
                });
            }, 1000);
        }
    }

    get classname(): string {
        if (this.test_definition.is_just_refreshed) {
            return "test-plan-test-definition-is-just-refreshed";
        }

        return "";
    }
}
</script>
