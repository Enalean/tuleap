<!--
  - Copyright (c) Enalean, 2018-present. All Rights Reserved.
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
    <div class="document-folder-content-quicklook">
        <section class="tlp-pane document-folder-pane">
            <div class="tlp-pane-container tlp-pane-section">
                <table class="tlp-table">
                    <thead>
                        <tr>
                            <th class="document-tree-head-name">
                                {{ $gettext("Name") }}
                            </th>
                            <template v-if="!toggle_quick_look">
                                <th
                                    class="document-tree-head-owner"
                                    data-test="document-folder-owner-information"
                                >
                                    {{ $gettext("Owner") }}
                                </th>
                                <th class="document-tree-head-updatedate">
                                    {{ $gettext("Last update date") }}
                                </th>
                            </template>
                        </tr>
                    </thead>

                    <tbody data-test="document-tree-content" data-shortcut-table>
                        <folder-content-row
                            v-for="item of folder_content"
                            v-bind:key="item.id"
                            v-bind:item="item"
                            v-bind:is_quick_look_displayed="toggle_quick_look"
                        />
                    </tbody>
                </table>
            </div>
        </section>
        <div
            v-if="should_display_preview"
            class="document-folder-right-container"
            data-test="document-quick-look"
        >
            <section
                class="tlp-pane document-quick-look-pane"
                v-bind:class="quick_look_dropzone_class"
                v-bind:data-item-id="item_id"
            >
                <quicklook-global
                    v-bind:currently_previewed_item="currently_previewed_item"
                    v-on:close-quick-look-event="closeQuickLook"
                />
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import FolderContentRow from "./FolderContentRow.vue";
import QuicklookGlobal from "../QuickLook/QuickLookGlobal.vue";
import { isFile, isFolder } from "../../helpers/type-check-helper";
import emitter from "../../helpers/emitter";
import { useState, useStore } from "vuex-composition-helpers";
import type { Item, RootState } from "../../type";
import { computed, onBeforeUnmount, onMounted } from "vue";
import { useRouter } from "../../helpers/use-router";

const $store = useStore();
const $router = useRouter();

const { folder_content, currently_previewed_item, toggle_quick_look, current_folder } = useState<
    Pick<
        RootState,
        "folder_content" | "currently_previewed_item" | "toggle_quick_look" | "current_folder"
    >
>(["folder_content", "currently_previewed_item", "toggle_quick_look", "current_folder"]);

const item_id = computed(() => currently_previewed_item.value?.id);
const quick_look_dropzone_class = computed(() => {
    if (currently_previewed_item.value === null) {
        return "";
    }

    return {
        "document-quick-look-folder-dropzone": isFolder(currently_previewed_item.value),
        "document-quick-look-file-dropzone": isFile(currently_previewed_item.value),
    };
});
const should_display_preview = computed(
    () => toggle_quick_look.value && currently_previewed_item.value !== null,
);

onMounted(() => {
    emitter.on("toggle-quick-look", toggleQuickLook);
});

onBeforeUnmount(() => {
    emitter.off("toggle-quick-look", toggleQuickLook);
});

async function toggleQuickLook(event: { details: { item: Item } }): Promise<void> {
    if (currently_previewed_item.value === null) {
        await displayQuickLook(event.details.item);
        return;
    }

    if (currently_previewed_item.value.id !== event.details.item.id) {
        await displayQuickLook(event.details.item);
        return;
    }

    if (!toggle_quick_look.value) {
        await displayQuickLook(event.details.item);
    } else {
        await closeQuickLook();
    }
}

async function displayQuickLook(item: Item): Promise<void> {
    await $router.replace({
        name: "preview",
        params: { preview_item_id: item.id },
    });

    $store.commit("updateCurrentlyPreviewedItem", item);
    $store.commit("toggleQuickLook", true);
}

async function closeQuickLook(): Promise<void> {
    if (current_folder.value !== null && current_folder.value.parent_id !== 0) {
        await $router.replace({
            name: "folder",
            params: { item_id: current_folder.value.id },
        });
    } else {
        await $router.replace({
            name: "root_folder",
        });
    }
    $store.commit("toggleQuickLook", false);
}
</script>
