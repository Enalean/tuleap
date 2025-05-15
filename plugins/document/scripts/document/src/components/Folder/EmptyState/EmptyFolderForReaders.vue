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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <section class="empty-state-page">
        <div class="empty-state-illustration">
            <empty-folder-for-readers-svg />
        </div>
        <h1 class="empty-state-title">
            {{ $gettext("This folder is empty") }}
        </h1>
        <p class="empty-state-text">
            {{ $gettext("There are no items here or you don't have permissions to see them.") }}
        </p>
        <router-link
            v-bind:to="route_to"
            class="empty-state-action tlp-button-primary tlp-button-large"
            v-if="can_go_to_parent"
        >
            <i class="fa-solid fa-arrow-right-long tlp-button-icon"></i>
            {{ $gettext("Go to parent folder") }}
        </router-link>
    </section>
</template>

<script setup lang="ts">
import EmptyFolderForReadersSvg from "../../svg/folder/EmptyFolderForReadersSvg.vue";
import type { Item, RootState } from "../../../type";
import { useNamespacedState } from "vuex-composition-helpers";
import { computed } from "vue";

interface RouterPayload {
    name: string;
    params?: {
        item_id?: number;
    };
}

const { current_folder_ascendant_hierarchy } = useNamespacedState<
    Pick<RootState, "current_folder_ascendant_hierarchy">
>(["current_folder_ascendant_hierarchy"]);

const index_of_parent = computed((): number => {
    return current_folder_ascendant_hierarchy.value.length - 2;
});

const parent = computed((): Item | null => {
    if (index_of_parent.value > 0) {
        return current_folder_ascendant_hierarchy.value[index_of_parent.value];
    }

    return null;
});

const route_to = computed((): RouterPayload => {
    const parent_payload = parent.value;
    return parent_payload !== null
        ? { name: "folder", params: { item_id: parent_payload.id } }
        : { name: "root_folder" };
});

const can_go_to_parent = (): boolean => {
    return index_of_parent.value >= -1;
};
</script>
