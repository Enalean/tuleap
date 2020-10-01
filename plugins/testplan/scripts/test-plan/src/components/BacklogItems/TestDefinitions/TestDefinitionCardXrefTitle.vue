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
    <div class="test-plan-test-definition-xref-title">
        <div class="tlp-dropdown">
            <a
                v-bind:href="go_to_test_def_link"
                class="test-plan-test-definition-xref"
                v-on:click.prevent
                ref="dropdownTrigger"
            >
                {{ test_definition.short_type }} #{{ test_definition.id }}
                <i class="fa fa-caret-down"></i>
            </a>

            <div class="tlp-dropdown-menu tlp-dropdown-menu-left" role="menu" ref="dropdownMenu">
                <a v-bind:href="go_to_test_def_link" class="tlp-dropdown-menu-item" role="menuitem">
                    <i class="fas fa-fw fa-pencil-alt"></i>
                    <translate
                        v-bind:translate-params="{
                            item_type: test_definition.short_type,
                            item_id: test_definition.id,
                        }"
                    >
                        Edit %{ item_type } #%{ item_id }
                    </translate>
                </a>
                <span
                    class="tlp-dropdown-menu-separator"
                    role="separator"
                    v-if="go_to_last_test_exec_link !== null"
                ></span>
                <a
                    v-bind:href="go_to_last_test_exec_link"
                    class="tlp-dropdown-menu-item"
                    role="menuitem"
                    v-if="go_to_last_test_exec_link !== null"
                    data-test="go-to-last-test-exec"
                >
                    <i class="fas fa-fw fa-long-arrow-alt-right"></i>
                    <translate v-bind:translate-params="{ release_name: milestone_title }">
                        Go to the last execution in release %{ release_name }
                    </translate>
                </a>
            </div>
        </div>
        {{ test_definition.summary }}
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { State } from "vuex-class";
import { createDropdown } from "tlp";
import { BacklogItem, TestDefinition } from "../../../type";
import {
    buildEditTestDefinitionItemLink,
    buildGoToTestExecutionLink,
} from "../../../helpers/BacklogItems/url-builder";

@Component
export default class TestDefinitionCardXrefTitle extends Vue {
    @State
    readonly project_id!: number;

    @State
    readonly milestone_id!: number;

    @State
    readonly milestone_title!: string;

    @Prop({ required: true })
    readonly backlog_item!: BacklogItem;

    @Prop({ required: true })
    readonly test_definition!: TestDefinition;

    $refs!: {
        dropdownTrigger: HTMLElement;
        dropdownMenu: HTMLElement;
    };

    mounted(): void {
        createDropdown(this.$refs.dropdownTrigger, { dropdown_menu: this.$refs.dropdownMenu });
    }

    get go_to_test_def_link(): string {
        return buildEditTestDefinitionItemLink(
            this.milestone_id,
            this.test_definition,
            this.backlog_item
        );
    }

    get go_to_last_test_exec_link(): string | null {
        return buildGoToTestExecutionLink(this.project_id, this.milestone_id, this.test_definition);
    }
}
</script>
