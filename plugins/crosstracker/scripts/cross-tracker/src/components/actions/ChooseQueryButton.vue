<!--
  - Copyright (c) Enalean, 2025-present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="tlp-dropdown">
        <button
            type="button"
            class="tlp-button-primary tlp-button-outline tlp-button-mini"
            ref="dropdown_trigger"
            data-test="choose-query-button"
        >
            {{ $gettext("Choose another query") }}
            <i class="fa-solid fa-caret-down tlp-button-icon" aria-hidden="true"></i>
        </button>
        <div class="tlp-dropdown-menu dropdown-menu-filter" role="menu" ref="dropdown_menu">
            <choose-query-menu
                v-bind:backend_query="backend_query"
                v-bind:queries="queries"
                v-bind:on_selected_query_callback="onSelectedQuery"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import { createDropdown } from "@tuleap/tlp-dropdown";
import { WIDGET_CONTAINER } from "../../injection-symbols";
import type { Query } from "../../type";
import { strictInject } from "@tuleap/vue-strict-inject";
import ChooseQueryMenu from "./ChooseQueryMenu.vue";

const dropdown_trigger = ref<HTMLElement>();
const dropdown_menu = ref<HTMLElement>();
let dropdown: Dropdown | null = null;

defineProps<{
    backend_query: Query;
    queries: ReadonlyArray<Query>;
}>();

const container = strictInject(WIDGET_CONTAINER);
onMounted((): void => {
    if (dropdown_trigger.value && dropdown_menu.value) {
        container.appendChild(dropdown_menu.value);
        dropdown = createDropdown(dropdown_trigger.value, {
            trigger: "click",
            dropdown_menu: dropdown_menu.value,
            keyboard: true,
        });
    }
});

function onSelectedQuery(): void {
    dropdown?.hide();
}

onBeforeUnmount((): void => {
    if (dropdown_menu.value) {
        container.removeChild(dropdown_menu.value);
    }

    dropdown?.destroy();
});
</script>
