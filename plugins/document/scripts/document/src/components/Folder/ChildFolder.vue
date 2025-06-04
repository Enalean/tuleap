<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <folder-container />
</template>

<script setup lang="ts">
import { useStore } from "vuex";
import FolderContainer from "./FolderContainer.vue";
import { useRoute } from "vue-router";
import { onMounted, watch } from "vue";
import { useState } from "vuex-composition-helpers";
import type { RootState } from "../../type";

const route = useRoute();
const store = useStore();
const props = defineProps<{
    item_id: number;
    preview_item_id: number;
}>();

const { current_folder, currently_previewed_item } = useState<
    Pick<RootState, "current_folder" | "currently_previewed_item">
>(["current_folder", "currently_previewed_item"]);

watch(
    () => route.path,
    () => {
        if (route.name === "preview") {
            store.dispatch("toggleQuickLook", props.preview_item_id);
            return;
        }

        store.dispatch("removeQuickLook");
        if (current_folder.value && current_folder.value.id !== props.item_id) {
            store.dispatch("loadFolder", props.item_id);
        }
    },
);

onMounted(async () => {
    if (route.name === "preview") {
        await store.dispatch("toggleQuickLook", props.preview_item_id);

        if (!current_folder.value && currently_previewed_item.value) {
            store.dispatch("loadFolder", currently_previewed_item.value.parent_id);
        }
    } else {
        store.dispatch("loadFolder", props.item_id);
        store.dispatch("removeQuickLook");
    }
});
</script>
