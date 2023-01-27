<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <div class="tlp-dropdown document-dropdown-menu-button">
        <button
            class="tlp-button-primary"
            v-bind:class="{
                'tlp-button-large': isInLargeMode,
                'tlp-button-small tlp-button-outline': isInQuickLookMode,
                'tlp-append tlp-dropdown-split-button-caret': isAppended,
                'tlp-button-ellipsis': !isAppended,
            }"
            ref="dropdown_button"
            type="button"
            data-test="document-drop-down-button"
            v-bind:aria-label="$gettext(`Open dropdown menu`)"
        >
            <i v-if="isAppended" class="fa-solid fa-caret-down" aria-hidden="true"></i>
            <i v-else class="fa-solid fa-ellipsis" aria-hidden="true"></i>
        </button>
        <div class="tlp-dropdown-menu document-dropdown-menu" role="menu">
            <slot></slot>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Dropdown } from "@tuleap/tlp-dropdown";
import {
    createDropdown,
    EVENT_TLP_DROPDOWN_SHOWN,
    EVENT_TLP_DROPDOWN_HIDDEN,
} from "@tuleap/tlp-dropdown";
import { EVENT_TLP_MODAL_SHOWN } from "@tuleap/tlp-modal";
import emitter from "../../../helpers/emitter";
import { onBeforeUnmount, onMounted, ref } from "vue";

defineProps<{ isInLargeMode: boolean; isInQuickLookMode: boolean; isAppended: boolean }>();

let dropdown: null | Dropdown = null;

const dropdown_button = ref<InstanceType<typeof HTMLButtonElement> | null>(null);
const emit = defineEmits<{
    (e: "dropdown-shown"): void;
    (e: "dropdown-hidden"): void;
}>();

onMounted(() => {
    if (!(dropdown_button.value instanceof HTMLButtonElement)) {
        return;
    }

    dropdown = createDropdown(dropdown_button.value);

    dropdown.addEventListener(EVENT_TLP_DROPDOWN_SHOWN, showDropdownEvent);
    dropdown.addEventListener(EVENT_TLP_DROPDOWN_HIDDEN, hideDropdownEvent);
    document.addEventListener(EVENT_TLP_MODAL_SHOWN, hideActionMenu);

    emitter.on("hide-action-menu", hideActionMenu);
});

onBeforeUnmount(() => {
    document.removeEventListener(EVENT_TLP_MODAL_SHOWN, hideActionMenu);

    emitter.off("hide-action-menu", hideActionMenu);
    if (!dropdown) {
        return;
    }
    dropdown.removeEventListener(EVENT_TLP_DROPDOWN_SHOWN, showDropdownEvent);
    dropdown.removeEventListener(EVENT_TLP_DROPDOWN_HIDDEN, hideDropdownEvent);
});

function hideActionMenu(): void {
    if (dropdown && dropdown.is_shown) {
        dropdown.hide();
    }
}

function showDropdownEvent(): void {
    emitter.emit("set-dropdown-shown", { is_dropdown_shown: true });
    emit("dropdown-shown");
}

function hideDropdownEvent(): void {
    emitter.emit("set-dropdown-shown", { is_dropdown_shown: false });
    emit("dropdown-hidden");
}
</script>
