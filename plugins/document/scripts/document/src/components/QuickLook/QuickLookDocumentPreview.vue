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
    <div v-if="isEmbedded(item) && is_loading_content" class="document-quicklook-content">
        <i
            class="fa-solid fa-spin fa-circle-notch document-preview-spinner"
            data-test="document-preview-spinner"
        ></i>
    </div>
    <div
        v-dompurify-html="item.embedded_file_properties?.content"
        class="document-quick-look-embedded"
        v-else-if="isEmbedded(item)"
        data-test="document-quick-look-embedded"
        v-bind:key="item.id"
    ></div>

    <div class="document-quick-look-image-container" v-else-if="is_an_image && item.user_can_write">
        <div class="document-quick-look-image-overlay">
            <i class="fa-regular fa-file-image document-quick-look-update-image-icon"></i>
            <span class="document-quick-look-dropzone-text">
                {{ $gettext("Drop to upload a new version") }}
            </span>
        </div>
        <img
            class="document-quick-look-image"
            v-if="isFile(item) && item.file_properties !== null"
            v-bind:src="item.file_properties.download_href"
            v-bind:alt="item.title"
        />
    </div>
    <div
        class="document-quick-look-image-container"
        v-else-if="is_an_image && !item.user_can_write"
    >
        <div class="document-quick-look-image-overlay">
            <i class="fa-solid fa-ban"></i>
            <span class="document-quick-look-dropzone-text">
                {{ $gettext("You are not allowed to upload a new version of this file") }}
            </span>
        </div>
        <img
            class="document-quick-look-image"
            v-if="isFile(item) && item.file_properties !== null"
            v-bind:src="item.file_properties.download_href"
            v-bind:alt="item.title"
        />
    </div>

    <div
        class="document-quick-look-folder-container"
        v-else-if="isFolder(item) && item.user_can_write"
    >
        <icon-quicklook-folder />
        <icon-quicklook-drop-into-folder />
        <span key="upload" class="document-quick-look-dropzone-text">
            {{ $gettext("Drop to upload in folder") }}
        </span>
    </div>
    <div
        class="document-quick-look-folder-container"
        v-else-if="isFolder(item) && !item.user_can_write"
    >
        <icon-quicklook-folder />
        <i class="fa-solid fa-ban"></i>
        <span key="folder" class="document-quick-look-dropzone-text tlp-text-danger">
            {{ $gettext("You are not allowed to write in this folder") }}
        </span>
    </div>

    <div class="document-quick-look-icon-container" v-else-if="item.user_can_write">
        <i class="document-quick-look-icon" v-bind:class="iconClass"></i>
        <span key="upload" class="document-quick-look-dropzone-text">
            {{ $gettext("Drop to upload a new version") }}
        </span>
    </div>
    <div class="document-quick-look-icon-container" v-else>
        <i class="document-quick-look-icon" v-bind:class="iconClass"></i>
        <i class="fa-solid fa-ban"></i>
        <span key="file" class="document-quick-look-dropzone-text">
            {{ $gettext("You are not allowed to upload a new version of this file") }}
        </span>
    </div>
</template>

<script setup lang="ts">
import IconQuicklookFolder from "../svg/svg-icons/IconQuicklookFolder.vue";
import IconQuicklookDropIntoFolder from "../svg/svg-icons/IconQuicklookDropIntoFolder.vue";
import { isEmbedded, isFile, isFolder } from "../../helpers/type-check-helper";
import { useState } from "vuex-composition-helpers";
import type { Item, RootState } from "../../type";
import { computed } from "vue";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

const { is_loading_currently_previewed_item } = useState<RootState>([
    "is_loading_currently_previewed_item",
]);

const props = defineProps<{
    iconClass: string;
    item: Item;
}>();

const is_loading_content = computed((): boolean => {
    if (!isEmbedded(props.item)) {
        return false;
    }

    return is_loading_currently_previewed_item.value === true;
});
const is_an_image = computed((): boolean => {
    if (!isFile(props.item) || props.item.file_properties === null) {
        return false;
    }
    return props.item.file_properties.file_type.includes("image");
});
</script>
