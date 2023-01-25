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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <button
        class="tlp-dropdown-menu-item document-clipboard-menu-item-paste"
        type="button"
        role="menuitem"
        v-if="can_item_be_pasted"
        v-on:click="doPasteItem"
        v-bind:class="{ 'tlp-dropdown-menu-item-disabled': pasting_in_progress }"
        v-bind:disabled="pasting_in_progress"
        data-shortcut-paste
    >
        <i
            class="tlp-dropdown-menu-item-icon document-clipboard-paste-icon-status"
            v-bind:class="[
                pasting_in_progress
                    ? 'fa-regular fa-spin fa-circle-o-notch'
                    : 'fa-solid fa-fw fa-paste',
            ]"
        ></i>
        <div class="document-clipboard-item-to-paste-container">
            <translate>Paste</translate>
            <span class="document-clipboard-item-to-paste">
                <i class="fa-regular fa-file"></i>
                {{ item_title }}
            </span>
        </div>
    </button>
</template>

<script setup lang="ts">
import type { Folder, State } from "../../../type";
import emitter from "../../../helpers/emitter";
import { isFolder } from "../../../helpers/type-check-helper";
import { CLIPBOARD_OPERATION_COPY, TYPE_FOLDER } from "../../../constants";
import {
    doesDocumentNameAlreadyExist,
    doesFolderNameAlreadyExist,
} from "../../../helpers/properties-helpers/check-item-title";
import { isItemDestinationIntoItself } from "../../../helpers/clipboard/clipboard-helpers";
import { useNamespacedActions, useState } from "vuex-composition-helpers";
import type { ClipboardState } from "../../../store/clipboard/module";
import { computed } from "vue";
import type { ClipboardActions } from "../../../store/clipboard/clipboard-actions";

const props = defineProps<{ destination: Folder }>();

const { folder_content, current_folder } = useState<
    Pick<State, "folder_content" | "current_folder">
>(["folder_content", "current_folder"]);

const { item_title, pasting_in_progress, operation_type, item_type, item_id } = useState<
    Pick<
        ClipboardState,
        "item_title" | "pasting_in_progress" | "operation_type" | "item_type" | "item_id"
    >
>("clipboard", ["item_title", "pasting_in_progress", "operation_type", "item_type", "item_id"]);

const { pasteItem } = useNamespacedActions<ClipboardActions>("clipboard", ["pasteItem"]);

const can_item_be_pasted = computed((): boolean => {
    if (
        item_title.value === null ||
        operation_type.value === null ||
        item_id.value === null ||
        !isFolder(props.destination) ||
        !props.destination.user_can_write
    ) {
        return false;
    }

    if (operation_type.value === CLIPBOARD_OPERATION_COPY) {
        return true;
    }

    if (item_type.value !== TYPE_FOLDER) {
        return !doesDocumentNameAlreadyExist(
            item_title.value,
            folder_content.value,
            props.destination
        );
    }

    return (
        !doesFolderNameAlreadyExist(item_title.value, folder_content.value, props.destination) &&
        !isItemDestinationIntoItself(folder_content.value, item_id.value, props.destination.id)
    );
});

async function doPasteItem(): Promise<void> {
    if (!pasting_in_progress.value) {
        emitter.emit("hide-action-menu");
    }

    await pasteItem({
        destination_folder: props.destination,
        current_folder: current_folder.value,
    });
}
</script>
