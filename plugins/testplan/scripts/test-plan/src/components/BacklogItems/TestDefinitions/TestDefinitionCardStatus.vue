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

<script lang="ts">
import { Component, Prop } from "vue-property-decorator";
import Vue from "vue";
import { TestDefinition } from "../../../type";
import { State } from "vuex-class";
import TestDefinitionCardStatusTooltipIcon from "./TestDefinitionCardStatusTooltipIcon.vue";
import { buildGoToTestExecutionLink } from "../../../helpers/BacklogItems/url-builder";
@Component({
    components: { TestDefinitionCardStatusTooltipIcon },
})
export default class TestDefinitionCardStatus extends Vue {
    @State
    readonly project_id!: number;

    @State
    readonly milestone_id!: number;

    @Prop({ required: true })
    readonly test_definition!: TestDefinition;

    get go_to_test_exec_link(): string | null {
        return buildGoToTestExecutionLink(this.project_id, this.milestone_id, this.test_definition);
    }
}
</script>
