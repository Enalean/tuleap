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
    <div class="test-plan-test-definition-metadata">
        <div class="tlp-dropdown">
            <a
                ref="dropdownTrigger"
                v-bind:href="go_to_test_def_link"
                class="test-plan-test-definition-xref"
                v-on:click.prevent
            >
                <span class="test-plan-test-definition-xref-text">
                    {{ test_definition.short_type }} #{{ test_definition.id }}
                </span>
                <i class="fa fa-caret-down test-plan-test-definition-xref-icon"></i>
            </a>

            <div ref="dropdownMenu" class="tlp-dropdown-menu tlp-dropdown-menu-left" role="menu">
                <a v-bind:href="go_to_test_def_link" class="tlp-dropdown-menu-item" role="menuitem">
                    <i class="fas fa-fw tlp-dropdown-menu-item-icon fa-pencil-alt"></i>
                    {{
                        $gettext("Edit %{ item_type } #%{ item_id }", {
                            item_type: test_definition.short_type,
                            item_id: String(test_definition.id),
                        })
                    }}
                </a>
                <span
                    v-if="go_to_last_test_exec_link !== null"
                    class="tlp-dropdown-menu-separator"
                    role="separator"
                ></span>
                <a
                    v-if="go_to_last_test_exec_link !== null"
                    v-bind:href="go_to_last_test_exec_link"
                    class="tlp-dropdown-menu-item"
                    role="menuitem"
                    data-test="go-to-last-test-exec"
                >
                    <i class="fas fa-fw tlp-dropdown-menu-item-icon fa-long-arrow-alt-right"></i>
                    {{
                        $gettext("Go to the last execution in release %{ release_name }", {
                            release_name: milestone_title,
                        })
                    }}
                </a>
            </div>
        </div>

        <div class="test-plan-test-definition-card-category-status">
            <span
                v-if="test_definition.category !== null"
                class="tlp-badge-secondary tlp-badge-outline test-plan-test-definition-category"
                data-test="test-category"
            >
                {{ test_definition.category }}
            </span>
            <div class="test-plan-test-definition-icons">
                <i
                    v-if="test_definition.automated_tests"
                    class="fa test-plan-test-definition-icon-automated-tests"
                    v-bind:class="automated_icon_status"
                    aria-hidden="true"
                    data-test="automated-test-icon"
                ></i>
                <test-definition-card-status v-bind:test_definition="test_definition" />
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import TestDefinitionCardStatus from "./TestDefinitionCardStatus.vue";
import { useState } from "vuex-composition-helpers";
import type { State } from "../../../store/type";
import type { BacklogItem, TestDefinition } from "../../../type";
import { computed, onMounted, ref } from "vue";
import { createDropdown } from "@tuleap/tlp-dropdown";
import {
    buildEditTestDefinitionItemLink,
    buildGoToTestExecutionLink,
} from "../../../helpers/BacklogItems/url-builder";

const { project_id, milestone_id, milestone_title } = useState<
    Pick<State, "project_id" | "milestone_id" | "milestone_title">
>(["project_id", "milestone_id", "milestone_title"]);

const props = defineProps<{
    backlog_item: BacklogItem;
    test_definition: TestDefinition;
}>();

const dropdownTrigger = ref<InstanceType<typeof Element>>();
const dropdownMenu = ref<InstanceType<typeof Element>>();

onMounted((): void => {
    if (dropdownTrigger.value && dropdownMenu.value) {
        createDropdown(dropdownTrigger.value, { dropdown_menu: dropdownMenu.value });
    }
});

const go_to_test_def_link = computed((): string => {
    return buildEditTestDefinitionItemLink(
        milestone_id.value,
        props.test_definition,
        props.backlog_item,
    );
});

const go_to_last_test_exec_link = computed((): string | null => {
    return buildGoToTestExecutionLink(project_id.value, milestone_id.value, props.test_definition);
});

const automated_icon_status = computed((): string => {
    switch (props.test_definition.test_status) {
        case "passed":
            return "fa-tlp-robot-happy test-plan-test-definition-icon-status-passed";
        case "failed":
            return "fa-tlp-robot-unhappy test-plan-test-definition-icon-status-failed";
        case "blocked":
            return "fa-tlp-robot test-plan-test-definition-icon-status-blocked";
        case "notrun":
            return "fa-tlp-robot test-plan-test-definition-icon-status-notrun";
        default:
            return "fa-tlp-robot";
    }
});
</script>
