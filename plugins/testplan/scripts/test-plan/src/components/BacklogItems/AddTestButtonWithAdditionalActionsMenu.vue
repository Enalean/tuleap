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
    <div v-if="can_add_button_be_displayed" class="tlp-dropdown" v-on:click.stop>
        <div class="tlp-dropdown-split-button test-plan-add-test-dropdown">
            <a
                v-bind:href="add_button_href"
                class="tlp-button-primary tlp-button-outline tlp-button-small tlp-dropdown-split-button-main"
                data-test="add-test-button"
            >
                {{ $gettext("Create a new test") }}
            </a>
            <button
                ref="dropdownTrigger"
                class="tlp-button-primary tlp-button-outline tlp-append tlp-dropdown-split-button-caret tlp-button-small"
            >
                <i class="fa fa-caret-down"></i>
            </button>
        </div>
        <div ref="dropdownMenu" class="tlp-dropdown-menu tlp-dropdown-menu-left" role="menu">
            <a v-bind:href="edit_backlog_item_href" class="tlp-dropdown-menu-item" role="menuitem">
                <i class="fas fa-fw fa-pencil-alt"></i>
                {{
                    $gettext("Edit %{ item_type } #%{ item_id }", {
                        item_type: backlog_item.short_type,
                        item_id: String(backlog_item.id),
                    })
                }}
            </a>
        </div>
    </div>
</template>
<script setup lang="ts">
import type { BacklogItem } from "../../type";
import { useState } from "vuex-composition-helpers";
import type { State } from "../../store/type";
import { computed, nextTick, ref, watch } from "vue";
import {
    buildCreateNewTestDefinitionLink,
    buildEditBacklogItemLink,
} from "../../helpers/BacklogItems/url-builder";
import { createDropdown } from "@tuleap/tlp-dropdown";

const props = defineProps<{
    backlog_item: BacklogItem;
}>();

const { testdefinition_tracker_id, milestone_id } = useState<
    Pick<State, "testdefinition_tracker_id" | "milestone_id">
>(["testdefinition_tracker_id", "milestone_id"]);

const dropdownTrigger = ref<InstanceType<typeof Element>>();
const dropdownMenu = ref<InstanceType<typeof Element>>();

const add_button_href = computed((): string => {
    if (!testdefinition_tracker_id.value) {
        return "";
    }
    return buildCreateNewTestDefinitionLink(
        testdefinition_tracker_id.value,
        milestone_id.value,
        props.backlog_item,
    );
});

const edit_backlog_item_href = computed((): string => {
    return buildEditBacklogItemLink(milestone_id.value, props.backlog_item);
});

const can_add_button_be_displayed = computed((): boolean => {
    return (
        props.backlog_item.is_expanded &&
        props.backlog_item.can_add_a_test &&
        Boolean(testdefinition_tracker_id.value) &&
        !props.backlog_item.is_loading_test_definitions &&
        !props.backlog_item.has_test_definitions_loading_error
    );
});

watch(can_add_button_be_displayed, (): void => {
    nextTick((): void => {
        if (!dropdownTrigger.value || !dropdownMenu.value) {
            return;
        }
        createDropdown(dropdownTrigger.value, { dropdown_menu: dropdownMenu.value });
    });
});
</script>
