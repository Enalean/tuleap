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
    <span
        class="tlp-tooltip test-plan-test-definition-test-status-tooltip-position"
        v-bind:data-tlp-tooltip="tooltip_content"
        data-test="test-status"
    >
        <i
            class="fa fa-fw"
            v-bind:class="icon_status"
            aria-hidden="true"
            data-test="test-status-icon"
        ></i>
    </span>
</template>

<script lang="ts">
import { Component, Prop } from "vue-property-decorator";
import Vue from "vue";
import { TestDefinition } from "../../../type";
import { State } from "vuex-class";

@Component
export default class TestDefinitionCardStatusTooltipIcon extends Vue {
    @State
    readonly milestone_title!: string;

    @Prop({ required: true })
    readonly test_definition!: TestDefinition;

    get tooltip_content(): string {
        switch (this.test_definition.test_status) {
            case "passed":
                return this.$gettext("Passed");
            case "failed":
                return this.$gettext("Failed");
            case "blocked":
                return this.$gettext("Blocked");
            case "notrun":
                return this.$gettext("Not run");
            default:
                return this.$gettextInterpolate(
                    this.$gettext("Not planned in release %{ release_name }"),
                    { release_name: this.milestone_title }
                );
        }
    }

    get icon_status(): string {
        switch (this.test_definition.test_status) {
            case "passed":
                return "fa-check-circle test-plan-test-definition-icon-status-passed";
            case "failed":
                return "fa-times-circle test-plan-test-definition-icon-status-failed";
            case "blocked":
                return "fa-exclamation-circle test-plan-test-definition-icon-status-blocked";
            case "notrun":
                return "fa-question-circle test-plan-test-definition-icon-status-notrun";
            default:
                return "fa-circle-thin test-plan-test-definition-icon-status-notplanned";
        }
    }
}
</script>
