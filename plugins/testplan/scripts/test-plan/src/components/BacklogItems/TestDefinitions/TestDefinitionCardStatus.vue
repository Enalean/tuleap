<!--
  - Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
    <a v-if="go_to_test_exec_link !== null" v-bind:href="go_to_test_exec_link">
        <test-definition-card-status-tooltip-icon v-bind:test_definition="test_definition" />
    </a>
    <test-definition-card-status-tooltip-icon v-else v-bind:test_definition="test_definition" />
</template>
<script setup lang="ts">
import TestDefinitionCardStatusTooltipIcon from "./TestDefinitionCardStatusTooltipIcon.vue";
import type { TestDefinition } from "../../../type";
import { useState } from "vuex-composition-helpers";
import type { State } from "../../../store/type";
import { computed } from "@vue/composition-api";
import { buildGoToTestExecutionLink } from "../../../helpers/BacklogItems/url-builder";

const props = defineProps<{
    test_definition: TestDefinition;
}>();

const { project_id, milestone_id } = useState<Pick<State, "project_id" | "milestone_id">>([
    "project_id",
    "milestone_id",
]);

const go_to_test_exec_link = computed((): string | null => {
    return buildGoToTestExecutionLink(project_id.value, milestone_id.value, props.test_definition);
});
</script>
<script lang="ts">
import { defineComponent } from "@vue/composition-api";

export default defineComponent({});
</script>
