<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
        v-if="item.user_can_write"
        class="tlp-dropdown-menu-item tlp-dropdown-menu-item-submenu"
        role="menuitem"
        aria-haspopup="true"
        v-bind:aria-expanded="is_dropdown_open"
        data-test="document-new-item"
        ref="button"
        v-bind:id="button_id"
    >
        <i class="fa-solid fa-fw fa-repeat tlp-dropdown-menu-item-icon" aria-hidden="true"></i>
        {{ $gettext("Convert to…") }}
        <new-version-empty-menu-options
            v-bind:item="item"
            class="tlp-dropdown-submenu tlp-dropdown-menu-side"
            ref="menu"
            v-bind:data-triggered-by="button_id"
        />
    </div>
</template>

<script setup lang="ts">
import type { Empty } from "../../../../type";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import {
    createDropdown,
    EVENT_TLP_DROPDOWN_HIDDEN,
    TRIGGER_HOVER_AND_CLICK,
    EVENT_TLP_DROPDOWN_SHOWN,
} from "@tuleap/tlp-dropdown";
import NewVersionEmptyMenuOptions from "./NewVersionEmptyMenuOptions.vue";

const props = defineProps<{ item: Empty }>();

const button_id = computed((): string => `document-folder-dropdown-menu-new-item-${props.item.id}`);
const button = ref<HTMLElement | null>(null);
const menu = ref<NewVersionEmptyMenuOptions | null>(null);
const dropdown = ref<Dropdown | null>(null);
const is_dropdown_open = ref<boolean | null>(null);

onMounted(() => {
    if (!button.value) {
        return;
    }

    if (!menu.value) {
        return;
    }

    dropdown.value = createDropdown(button.value, {
        dropdown_menu: menu.value.$el,
        trigger: TRIGGER_HOVER_AND_CLICK,
    });
    dropdown.value.addEventListener(EVENT_TLP_DROPDOWN_SHOWN, onDropdownOpen);
    dropdown.value.addEventListener(EVENT_TLP_DROPDOWN_HIDDEN, onDropdownClose);
});

onBeforeUnmount(() => {
    if (dropdown.value) {
        dropdown.value.removeEventListener(EVENT_TLP_DROPDOWN_SHOWN, onDropdownOpen);
        dropdown.value.removeEventListener(EVENT_TLP_DROPDOWN_HIDDEN, onDropdownClose);
    }
});

function onDropdownOpen(): void {
    is_dropdown_open.value = true;
}

function onDropdownClose(): void {
    is_dropdown_open.value = null;
}
</script>
