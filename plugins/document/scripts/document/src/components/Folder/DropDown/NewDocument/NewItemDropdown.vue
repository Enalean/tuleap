<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
  -->

<template>
    <div class="tlp-dropdown" v-if="is_item_a_folder && item.user_can_write">
        <button
            type="button"
            class="tlp-button-primary"
            v-bind:class="classes"
            data-test="document-new-item"
            data-shortcut-create-document
            ref="trigger"
        >
            <i class="fa-solid fa-plus tlp-button-icon" aria-hidden="true"></i>
            {{ $gettext("New") }}
            <i class="fa-solid fa-caret-down tlp-button-icon" aria-hidden="true" ref="anchor"></i>
        </button>

        <new-item-menu-options
            v-bind:item="item"
            ref="menu"
            class="document-dropdown-menu-for-new-item-button"
        />
    </div>
</template>

<script setup lang="ts">
import { isFolder } from "../../../../helpers/type-check-helper";
import type { Item } from "../../../../type";
import { computed, onMounted, ref } from "vue";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import { createDropdown } from "@tuleap/tlp-dropdown";
import NewItemMenuOptions from "./NewItemMenuOptions.vue";

const props = withDefaults(defineProps<{ item: Item | null; is_in_quicklook?: boolean }>(), {
    is_in_quicklook: false,
});

const is_item_a_folder = computed((): boolean => {
    if (!props.item) {
        return false;
    }
    return isFolder(props.item);
});

const classes = computed((): string => (props.is_in_quicklook ? "tlp-button-small" : ""));

const trigger = ref<HTMLElement | null>(null);
const anchor = ref<HTMLElement | null>(null);
const dropdown = ref<Dropdown | null>(null);

onMounted(() => {
    if (!(trigger.value instanceof HTMLElement)) {
        return;
    }

    if (!(anchor.value instanceof HTMLElement)) {
        return;
    }

    dropdown.value = createDropdown(trigger.value, {
        anchor: anchor.value,
    });
});
</script>
