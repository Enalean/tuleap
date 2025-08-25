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
        v-bind:class="{ 'tlp-dropdown-menu-item-disabled': clipboard.pasting_in_progress }"
        v-bind:disabled="clipboard.pasting_in_progress"
        data-shortcut-paste
    >
        <i
            class="tlp-dropdown-menu-item-icon document-clipboard-paste-icon-status"
            v-bind:class="[
                clipboard.pasting_in_progress
                    ? 'fa-regular fa-spin fa-circle-o-notch'
                    : 'fa-solid fa-fw fa-paste',
            ]"
        ></i>
        <div class="document-clipboard-item-to-paste-container">
            {{ $gettext("Paste") }}
            <span class="document-clipboard-item-to-paste">
                <i class="fa-regular fa-file"></i>
                {{ clipboard.item_title }}
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
import { useState } from "vuex-composition-helpers";
import { useClipboardStore } from "../../../stores/clipboard";
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import { useStore } from "vuex";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT_ID, USER_ID } from "../../../configuration-keys";

const { $gettext } = useGettext();

const props = defineProps<{ destination: Folder }>();

const { folder_content, current_folder } = useState<
    Pick<State, "folder_content" | "current_folder">
>(["folder_content", "current_folder"]);

const store = useStore();

const user_id = strictInject(USER_ID);
const project_id = strictInject(PROJECT_ID);
const clipboard = useClipboardStore(store, project_id, user_id);

const can_item_be_pasted = computed((): boolean => {
    if (
        clipboard.item_title === null ||
        clipboard.operation_type === null ||
        clipboard.item_id === null ||
        !isFolder(props.destination) ||
        !props.destination.user_can_write
    ) {
        return false;
    }

    if (clipboard.operation_type === CLIPBOARD_OPERATION_COPY) {
        return true;
    }

    if (clipboard.item_type !== TYPE_FOLDER) {
        return !doesDocumentNameAlreadyExist(
            clipboard.item_title,
            folder_content.value,
            props.destination,
        );
    }

    return (
        !doesFolderNameAlreadyExist(
            clipboard.item_title,
            folder_content.value,
            props.destination,
        ) &&
        !isItemDestinationIntoItself(folder_content.value, clipboard.item_id, props.destination.id)
    );
});

async function doPasteItem(): Promise<void> {
    if (!clipboard.pasting_in_progress) {
        emitter.emit("hide-action-menu");
    }

    await clipboard.pasteItem({
        destination_folder: props.destination,
        current_folder: current_folder.value,
    });
}
</script>
