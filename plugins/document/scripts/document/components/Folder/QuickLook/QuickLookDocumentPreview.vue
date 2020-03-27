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
            class="fa fa-spin fa-circle-o-notch document-preview-spinner"
            data-test="document-preview-spinner"
        ></i>
    </div>
    <div
        v-dompurify-html="currently_previewed_item.embedded_file_properties.content"
        class="document-quick-look-embedded"
        v-else-if="is_an_embedded_file"
        data-test="document-quick-look-embedded"
    ></div>

    <div
        class="document-quick-look-image-container"
        v-else-if="is_an_image && currently_previewed_item.user_can_write"
    >
        <div class="document-quick-look-image-overlay">
            <i class="fa fa-file-image-o document-quick-look-update-image-icon"></i>
            <span class="document-quick-look-dropzone-text" v-translate>
                Drop to upload a new version
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
            <i class="fa fa-ban"></i>
            <span class="document-quick-look-dropzone-text" v-translate>
                You are not allowed to upload a new version of this file
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
        v-else-if="
            is_item_a_folder(currently_previewed_item) && currently_previewed_item.user_can_write
        "
    >
        <icon-quicklook-folder />
        <icon-quicklook-drop-into-folder />
        <span key="upload" class="document-quick-look-dropzone-text" v-translate>
            Drop to upload in folder
        </span>
    </div>
    <div
        class="document-quick-look-folder-container"
        v-else-if="
            is_item_a_folder(currently_previewed_item) && !currently_previewed_item.user_can_write
        "
    >
        <icon-quicklook-folder />
        <i class="fa fa-ban"></i>
        <span key="folder" class="document-quick-look-dropzone-text tlp-text-danger" v-translate>
            You are not allowed to write in this folder
        </span>
    </div>

    <div
        class="document-quick-look-icon-container"
        v-else-if="currently_previewed_item.user_can_write"
    >
        <i class="fa document-quick-look-icon" v-bind:class="iconClass"></i>
        <span key="upload" class="document-quick-look-dropzone-text" v-translate>
            Drop to upload a new version
        </span>
    </div>
    <div class="document-quick-look-icon-container" v-else>
        <i class="fa document-quick-look-icon" v-bind:class="iconClass"></i>
        <i class="fa fa-ban"></i>
        <span key="file" class="document-quick-look-dropzone-text" v-translate>
            You are not allowed to upload a new version of this file
        </span>
    </div>
</template>
<script>
import { mapState, mapGetters } from "vuex";
import IconQuicklookFolder from "../../svg/svg-icons/IconQuicklookFolder.vue";
import IconQuicklookDropIntoFolder from "../../svg/svg-icons/IconQuicklookDropIntoFolder.vue";
import { TYPE_EMBEDDED } from "../../../constants.js";

export default {
    components: {
        IconQuicklookFolder,
        IconQuicklookDropIntoFolder,
    },
    props: {
        iconClass: String,
    },
    computed: {
        ...mapState(["currently_previewed_item", "is_loading_currently_previewed_item"]),
        ...mapGetters(["is_item_a_folder"]),
        is_loading_content() {
            if (!this.is_an_embedded_file) {
                return false;
            }

            return this.is_loading_currently_previewed_item === true;
        },
        is_an_image() {
            if (!this.currently_previewed_item.file_properties) {
                return false;
            }
            return (
                this.currently_previewed_item.file_properties &&
                this.currently_previewed_item.file_properties.file_type.includes("image")
            );
        },
        is_an_embedded_file() {
            return this.currently_previewed_item.type === TYPE_EMBEDDED;
        },
    },
};
</script>
