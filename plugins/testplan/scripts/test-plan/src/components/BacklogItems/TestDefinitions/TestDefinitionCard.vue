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
    <div
        class="test-plan-test-definition-card"
        v-bind:class="{
            'test-plan-test-definition-is-just-refreshed': test_definition.is_just_refreshed,
        }"
    >
        <test-definition-card-xref-category-status
            v-bind:test_definition="test_definition"
            v-bind:backlog_item="backlog_item"
        />
        <p class="test-plan-test-definition-title">{{ test_definition.summary }}</p>
    </div>
</template>

<script setup lang="ts">
import TestDefinitionCardXrefCategoryStatus from "./TestDefinitionCardXrefCategoryStatus.vue";
import type { BacklogItem, TestDefinition } from "../../../type";
import { useNamespacedMutations } from "vuex-composition-helpers";
import type { BacklogItemMutations } from "../../../store/backlog-item/backlog-item-mutations";
import { onMounted } from "vue";

const props = defineProps<{
    test_definition: TestDefinition;
    backlog_item: BacklogItem;
}>();

const { removeIsJustRefreshedFlagOnTestDefinition } = useNamespacedMutations<
    Pick<BacklogItemMutations, "removeIsJustRefreshedFlagOnTestDefinition">
>("backlog_item", ["removeIsJustRefreshedFlagOnTestDefinition"]);

onMounted((): void => {
    if (props.test_definition.is_just_refreshed) {
        setTimeout(() => {
            removeIsJustRefreshedFlagOnTestDefinition({
                backlog_item: props.backlog_item,
                test_definition: props.test_definition,
            });
        }, 1000);
    }
});
</script>
<script lang="ts">
import { defineComponent } from "vue";

export default defineComponent({});
</script>
