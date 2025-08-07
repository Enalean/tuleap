<!--
  - Copyright (c) Enalean 2022 - Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div class="tlp-dropdown document-search-dropdown" ref="dropdown_container">
        <button
            class="tlp-button-primary tlp-button-small"
            ref="dropdown_button"
            type="button"
            data-test="trigger"
            v-bind:aria-label="button_label"
            v-bind:id="button_id"
        >
            <i class="fa-solid fa-ellipsis"></i>
            <i class="fa-solid fa-caret-down tlp-button-icon"></i>
        </button>
        <div
            class="tlp-dropdown-menu document-dropdown-menu"
            role="menu"
            ref="dropdown_menu"
            v-bind:data-triggered-by="button_id"
        >
            <drop-down-menu-tree-view v-if="should_menu_be_displayed" v-bind:item="real_item" />
            <div class="document-search-dropdown-spinner" data-test="spinner" v-else>
                <i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import type { Item, ItemSearchResult } from "../../../../type";
import { useActions } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import {
    createDropdown,
    EVENT_TLP_DROPDOWN_HIDDEN,
    EVENT_TLP_DROPDOWN_SHOWN,
} from "@tuleap/tlp-dropdown";
import emitter from "../../../../helpers/emitter";
import DropDownMenuTreeView from "../../../Folder/DropDown/DropDownMenuTreeView.vue";
import type { RootActionsRetrieve } from "../../../../store/actions-retrieve";

const props = defineProps<{ item: ItemSearchResult }>();

const button_id = computed((): string => {
    return "document-search-dropdown-trigger-" + props.item.id;
});

const { $gettext } = useGettext();

const button_label = ref($gettext(`Open dropdown menu`));

const real_item = ref<Item | null>(null);

const { loadDocument } = useActions<Pick<RootActionsRetrieve, "loadDocument">>(["loadDocument"]);

const emit = defineEmits<{
    (e: "dropdown-shown"): void;
    (e: "dropdown-hidden"): void;
}>();

async function onDropdownShown(): Promise<void> {
    emit("dropdown-shown");
    if (!real_item.value) {
        const item = await loadDocument(props.item.id);
        if (item) {
            real_item.value = item;
        }
    }
}

const should_menu_be_displayed = computed((): boolean => {
    return real_item.value !== null;
});

function onDropdownHidden(): void {
    emit("dropdown-hidden");
}

const dropdown_button = ref<InstanceType<typeof HTMLButtonElement> | null>(null);
const dropdown_menu = ref<InstanceType<typeof HTMLElement> | null>(null);
const dropdown_container = ref<InstanceType<typeof HTMLElement> | null>(null);

let dropdown: null | Dropdown = null;

function hideActionMenu(): void {
    if (dropdown && dropdown.is_shown) {
        dropdown.hide();
    }
}

onMounted(() => {
    if (!dropdown_container.value) {
        return;
    }
    if (!dropdown_button.value) {
        return;
    }
    if (!dropdown_menu.value) {
        return;
    }

    dropdown = createDropdown(dropdown_button.value);
    dropdown.addEventListener(EVENT_TLP_DROPDOWN_SHOWN, onDropdownShown);
    dropdown.addEventListener(EVENT_TLP_DROPDOWN_HIDDEN, onDropdownHidden);
    emitter.on("hide-action-menu", hideActionMenu);

    document.body.appendChild(dropdown_menu.value);
});

onBeforeUnmount(() => {
    emitter.off("hide-action-menu", hideActionMenu);
    if (dropdown) {
        dropdown.removeEventListener(EVENT_TLP_DROPDOWN_SHOWN, onDropdownShown);
        dropdown.removeEventListener(EVENT_TLP_DROPDOWN_HIDDEN, onDropdownHidden);
    }

    if (dropdown_menu.value) {
        dropdown_menu.value.remove();
    }
});

defineExpose({ should_menu_be_displayed });
</script>
