<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
  -
  -->

<template>
    <div data-test="folder-title">
        <i
            class="document-folder-icon-color fa-fw document-folder-toggle document-folder-content-icon"
            v-bind:class="{
                'fa-solid fa-caret-down': !is_closed,
                'fa-solid fa-caret-right': is_closed,
            }"
            v-on:click="toggle"
            v-on:keyup.enter="toggle"
            data-test="toggle"
            tabindex="0"
            data-shortcut-folder-toggle
        ></i>
        <i
            class="document-folder-icon-color fa-fw document-folder-content-icon"
            data-test="document-folder-icon-open"
            v-bind:class="{
                'fa-solid fa-folder': is_closed,
                'fa-regular fa-folder-open': is_folder_loaded_and_open,
                'fa-solid fa-circle-notch fa-spin': is_loading,
            }"
        ></i>
        <a
            v-on:click.prevent="goToFolder"
            v-bind:href="folder_href"
            class="document-folder-subitem-link"
            data-test="document-go-to-folder-link"
            draggable="false"
        >
            {{ item.title
            }}<i
                class="fas document-action-icon"
                v-bind:class="ACTION_ICON_FOLDER"
                aria-hidden="true"
            ></i>
        </a>
    </div>
</template>

<script setup lang="ts">
import type { Folder, State } from "../../../type";
import { ACTION_ICON_FOLDER } from "../../../constants";
import {
    useActions,
    useGetters,
    useMutations,
    useNamespacedActions,
    useState,
    useStore,
} from "vuex-composition-helpers";
import { useRouter } from "../../../helpers/use-router";
import { computed, onMounted, ref } from "vue";
import type { PreferenciesActions } from "../../../store/preferencies/preferencies-actions";
import { abortCurrentUploads } from "../../../helpers/abort-current-uploads";
import { useGettext } from "vue3-gettext";
import type { RootGetter } from "../../../store/getters";

const router = useRouter();
const { $gettext } = useGettext();
const store = useStore();

const props = defineProps<{ item: Folder }>();

const { files_uploads_list } = useState<Pick<State, "files_uploads_list">>(["files_uploads_list"]);

const {
    initializeFolderProperties,
    appendFolderToAscendantHierarchy,
    unfoldFolderContent,
    toggleCollapsedFolderHasUploadingContent,
    foldFolderContent,
} = useMutations([
    "initializeFolderProperties",
    "appendFolderToAscendantHierarchy",
    "unfoldFolderContent",
    "toggleCollapsedFolderHasUploadingContent",
    "foldFolderContent",
]);

const { getSubfolderContent } = useActions(["getSubfolderContent"]);

const { setUserPreferenciesForFolder } = useNamespacedActions<PreferenciesActions>("preferencies", [
    "setUserPreferenciesForFolder",
]);

const { is_uploading } = useGetters<Pick<RootGetter, "is_uploading">>(["is_uploading"]);

let is_closed = ref(true);
let is_loading = ref(false);
let have_children_been_loaded = ref(false);

const folder_href = computed((): string => {
    const { href } = router.resolve({
        name: "folder",
        params: { item_id: String(props.item.id) },
    });

    return href;
});

const is_folder_loaded_and_open = computed((): boolean => {
    return !is_loading.value && !is_closed.value;
});

const has_uploading_content = computed((): boolean => {
    const uploading_content = files_uploads_list.value.find(
        (file) => file.parent_id === props.item.id && file.progress && file.progress > 0,
    );

    return uploading_content !== null;
});

onMounted((): void => {
    initializeFolderProperties(props.item);
    if (props.item.is_expanded) {
        open();
    }
});

async function goToFolder(): Promise<void> {
    if (!is_uploading.value || abortCurrentUploads($gettext, store)) {
        await doGoToFolder();
    }
}

async function doGoToFolder() {
    appendFolderToAscendantHierarchy(props.item);
    await router.push({ name: "folder", params: { item_id: String(props.item.id) } });
}

async function loadChildren(): Promise<void> {
    is_loading.value = true;

    await getSubfolderContent(props.item.id);

    is_loading.value = false;
    have_children_been_loaded.value = true;
}

function open(): void {
    if (!have_children_been_loaded.value) {
        loadChildren();
    }

    is_closed.value = false;

    unfoldFolderContent(props.item.id);
}

function toggle(): void {
    if (is_closed.value) {
        toggleCollapsedFolderHasUploadingContent({ collapsed_folder: props.item, toggle: false });
        open();
    } else {
        foldFolderContent(props.item.id);
        toggleCollapsedFolderHasUploadingContent({
            collapsed_folder: props.item,
            has_uploading_content,
        });

        is_closed.value = true;
    }

    setUserPreferenciesForFolder({
        folder_id: props.item.id,
        should_be_closed: is_closed.value,
    });
}
</script>
