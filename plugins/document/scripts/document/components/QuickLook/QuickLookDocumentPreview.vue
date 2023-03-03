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
    <div v-if="is_an_embedded_file && is_loading_content" class="document-quicklook-content">
        <i
            class="fa-solid fa-spin fa-circle-notch document-preview-spinner"
            data-test="document-preview-spinner"
        ></i>
    </div>
    <div
        v-dompurify-html="currently_previewed_item.embedded_file_properties.content"
        class="document-quick-look-embedded"
        v-else-if="is_an_embedded_file"
        data-test="document-quick-look-embedded"
        v-bind:key="currently_previewed_item.id"
    ></div>

    <div
        class="document-quick-look-image-container"
        v-else-if="is_an_image && currently_previewed_item.user_can_write"
    >
        <div class="document-quick-look-image-overlay">
            <i class="fa-regular fa-file-image document-quick-look-update-image-icon"></i>
            <span class="document-quick-look-dropzone-text">
                {{ $gettext("Drop to upload a new version") }}
            </span>
        </div>
        <img
            class="document-quick-look-image"
            v-bind:src="currently_previewed_item.file_properties.download_href"
            v-bind:alt="currently_previewed_item.title"
        />
    </div>
    <div
        class="document-quick-look-image-container"
        v-else-if="is_an_image && !currently_previewed_item.user_can_write"
    >
        <div class="document-quick-look-image-overlay">
            <i class="fa-solid fa-ban"></i>
            <span class="document-quick-look-dropzone-text">
                {{ $gettext("You are not allowed to upload a new version of this file") }}
            </span>
        </div>
        <img
            class="document-quick-look-image"
            v-bind:src="currently_previewed_item.file_properties.download_href"
            v-bind:alt="currently_previewed_item.title"
        />
    </div>

    <div
        class="document-quick-look-folder-container"
        v-else-if="is_item_a_folder && currently_previewed_item.user_can_write"
    >
        <icon-quicklook-folder />
        <icon-quicklook-drop-into-folder />
        <span key="upload" class="document-quick-look-dropzone-text">
            {{ $gettext("Drop to upload in folder") }}
        </span>
    </div>
    <div
        class="document-quick-look-folder-container"
        v-else-if="is_item_a_folder && !currently_previewed_item.user_can_write"
    >
        <icon-quicklook-folder />
        <i class="fa-solid fa-ban"></i>
        <span key="folder" class="document-quick-look-dropzone-text tlp-text-danger">
            {{ $gettext("You are not allowed to write in this folder") }}
        </span>
    </div>

    <div
        class="document-quick-look-icon-container"
        v-else-if="currently_previewed_item.user_can_write"
    >
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
import type { State } from "../../type";
import { computed } from "vue";

const { is_loading_currently_previewed_item, currently_previewed_item } = useState<
    Pick<State, "is_loading_currently_previewed_item" | "currently_previewed_item">
>(["is_loading_currently_previewed_item", "currently_previewed_item"]);

defineProps<{ iconClass: string }>();

const is_an_embedded_file = computed((): boolean => {
    return currently_previewed_item.value !== null && isEmbedded(currently_previewed_item.value);
});

const is_loading_content = computed((): boolean => {
    if (!is_an_embedded_file.value) {
        return false;
    }

    return is_loading_currently_previewed_item.value === true;
});
const is_an_image = computed((): boolean => {
    const item = currently_previewed_item.value;
    if (item === null || !isFile(item) || item.file_properties === null) {
        return false;
    }
    return item.file_properties.file_type.includes("image");
});

const is_item_a_folder = computed((): boolean => {
    return currently_previewed_item.value !== null && isFolder(currently_previewed_item.value);
});
</script>
