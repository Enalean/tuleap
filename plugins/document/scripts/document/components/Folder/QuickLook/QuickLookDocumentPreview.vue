<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
    <!-- eslint-disable-next-line vue/no-v-html -->
    <div v-html="escaped_embedded_content"
         class="document-quick-look-embedded"
         v-if="is_embedded"
    ></div>

    <div class="document-quick-look-image-container" v-else-if="is_an_image && item.user_can_write">
        <div class="document-quick-look-image-overlay">
            <i class="fa fa-file-image-o document-quick-look-update-image-icon"></i>
            <span class="document-quick-look-dropzone-text" v-translate>
                Drop to upload a new version
            </span>
        </div>
        <img class="document-quick-look-image" v-bind:src="item.file_properties.html_url" v-bind:alt="item.title">
    </div>
    <div class="document-quick-look-image-container" v-else-if="is_an_image && ! item.user_can_write">
        <div class="document-quick-look-image-overlay">
            <i class="fa fa-ban"></i>
            <span class="document-quick-look-dropzone-text" v-translate>
                You are not allowed to update this file
            </span>
        </div>
        <img class="document-quick-look-image" v-bind:src="item.file_properties.html_url" v-bind:alt="item.title">
    </div>

    <div class="document-quick-look-folder-container" v-else-if="is_a_folder && item.user_can_write">
        <icon-quicklook-folder/>
        <icon-quicklook-drop-into-folder/>
        <span key="upload"
              class="document-quick-look-dropzone-text"
              v-translate
        >
            Drop to upload in folder
        </span>
    </div>
    <div class="document-quick-look-folder-container" v-else-if="is_a_folder && ! item.user_can_write">
        <icon-quicklook-folder/>
        <i class="fa fa-ban"></i>
        <span key="folder"
              class="document-quick-look-dropzone-text tlp-text-danger"
              v-translate
        >
            You are not allowed to write in this folder
        </span>
    </div>

    <div class="document-quick-look-icon-container" v-else-if="item.user_can_write">
        <i class="fa document-quick-look-icon"
           v-bind:class="iconClass"
        ></i>
        <span key="upload"
              class="document-quick-look-dropzone-text"
              v-translate
        >
            Drop to upload a new version
        </span>
    </div>
    <div class="document-quick-look-icon-container" v-else>
        <i class="fa document-quick-look-icon"
           v-bind:class="iconClass"
        ></i>
        <i class="fa fa-ban"></i>
        <span key="file"
              class="document-quick-look-dropzone-text"
              v-translate
        >
            You are not allowed to update this file
        </span>
    </div>
</template>
<script>
import dompurify from "dompurify";
import { TYPE_EMBEDDED, TYPE_FOLDER } from "../../../constants.js";
import IconQuicklookFolder from "../../svg-icons/IconQuicklookFolder.vue";
import IconQuicklookDropIntoFolder from "../../svg-icons/IconQuicklookDropIntoFolder.vue";

export default {
    components: {
        IconQuicklookFolder,
        IconQuicklookDropIntoFolder
    },
    props: {
        item: Object,
        iconClass: String
    },
    computed: {
        is_an_image() {
            if (!this.item.file_properties) {
                return false;
            }
            return (
                this.item.file_properties && this.item.file_properties.file_type.includes("image")
            );
        },
        is_embedded() {
            return this.item.type === TYPE_EMBEDDED;
        },
        escaped_embedded_content() {
            if (!this.item.embedded_file_properties) {
                return;
            }
            return dompurify.sanitize(this.item.embedded_file_properties.content);
        },
        is_a_folder() {
            return this.item.type === TYPE_FOLDER;
        }
    }
};
</script>
