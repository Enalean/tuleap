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
    <div class="tlp-dropdown">
        <button
            type="button"
            class="tlp-button-primary tlp-button-small"
            data-test="document-new-item"
            data-shortcut-create-document
            ref="trigger"
        >
            <i class="fa-solid fa-repeat tlp-button-icon" aria-hidden="true"></i>
            {{ $gettext("Convert to…") }}
            <i
                class="fa-solid fa-caret-down tlp-button-icon-right"
                aria-hidden="true"
                ref="anchor"
            ></i>
        </button>

        <new-version-empty-menu-options
            v-bind:item="item"
            ref="menu"
            class="document-dropdown-menu-for-convert-button"
        />
    </div>
</template>

<script setup lang="ts">
import type { Empty } from "../../../../type";
import { onMounted, ref } from "vue";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import { createDropdown } from "@tuleap/tlp-dropdown";
import NewVersionEmptyMenuOptions from "./NewVersionEmptyMenuOptions.vue";

defineProps<{ item: Empty }>();

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
