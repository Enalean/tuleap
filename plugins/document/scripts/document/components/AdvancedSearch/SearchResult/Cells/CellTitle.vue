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
    <th
        class="document-search-result-title-cell"
        v-bind:class="{ 'document-search-result-title-cell-dropdown-shown': is_dropdown_shown }"
    >
        <search-item-dropdown
            v-bind:item="item"
            v-on:dropdown-shown="onDropdownShown"
            v-on:dropdown-hidden="onDropdownHidden"
        />
        <div class="document-search-result-title">
            <i
                class="fa fa-fw document-search-result-icon"
                v-bind:class="icon_classes"
                aria-hidden="true"
            ></i>
            <a
                v-if="href"
                v-bind:href="href"
                v-bind:title="item.title"
                class="document-folder-subitem-link"
                data-test="link"
            >
                {{ item.title }}
            </a>
            <router-link
                v-else-if="in_app_link"
                v-bind:to="in_app_link"
                v-bind:title="item.title"
                class="document-folder-subitem-link"
                data-test="router-link"
            >
                {{ item.title }}
            </router-link>
            <span v-else v-bind:title="item.title">{{ item.title }}</span>
        </div>
    </th>
</template>
<script setup lang="ts">
import type { Folder, ItemSearchResult } from "../../../../type";
import { computed, ref } from "vue";
import {
    ICON_EMBEDDED,
    ICON_EMPTY,
    ICON_FOLDER_ICON,
    ICON_LINK,
    ICON_WIKI,
    TYPE_EMBEDDED,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../../../constants";
import { iconForMimeType } from "../../../../helpers/icon-for-mime-type";
import type { Route } from "vue-router/types/router";
import { useState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../../store/configuration";
import SearchItemDropdown from "./SearchItemDropdown.vue";

const { current_folder } = useState<{ current_folder: Folder }>(["current_folder"]);

const { project_id } = useState<Pick<ConfigurationState, "project_id">>("configuration", [
    "project_id",
]);

const props = defineProps<{ item: ItemSearchResult }>();

const icon_classes = computed((): string => {
    switch (props.item.type) {
        case TYPE_FILE:
            if (!props.item.file_properties) {
                return ICON_EMPTY;
            }

            return iconForMimeType(props.item.file_properties.file_type);
        case TYPE_EMBEDDED:
            return ICON_EMBEDDED;
        case TYPE_FOLDER:
            return ICON_FOLDER_ICON;
        case TYPE_LINK:
            return ICON_LINK;
        case TYPE_WIKI:
            return ICON_WIKI;
        default:
            return ICON_EMPTY;
    }
});

const href = computed((): string | null => {
    if (props.item.type === TYPE_FILE && props.item.file_properties) {
        return props.item.file_properties.download_href;
    }

    if (props.item.type === TYPE_LINK || props.item.type === TYPE_WIKI) {
        return `/plugins/docman/?group_id=${project_id.value}&action=show&id=${props.item.id}`;
    }

    return null;
});

const in_app_link = computed((): Partial<Route> | null => {
    if (props.item.type === TYPE_EMBEDDED) {
        const item_id = String(props.item.id);
        const folder_id =
            props.item.parents.length > 0
                ? String(props.item.parents[0].id)
                : String(current_folder.value.id);

        return {
            name: "item",
            params: {
                folder_id,
                item_id,
            },
        };
    }

    if (props.item.type === TYPE_FOLDER) {
        return {
            name: "folder",
            params: {
                item_id: String(props.item.id),
            },
        };
    }

    return null;
});

const is_dropdown_shown = ref(false);

function onDropdownShown(): void {
    is_dropdown_shown.value = true;
}

function onDropdownHidden(): void {
    is_dropdown_shown.value = false;
}
</script>

<script lang="ts">
import { defineComponent } from "vue";

export default defineComponent({});
</script>
