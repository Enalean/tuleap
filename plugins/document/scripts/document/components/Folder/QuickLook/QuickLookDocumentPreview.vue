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
    <div class="document-quick-look-image" v-if="is_an_image">
        <div v-if="item.user_can_write" class="document-quick-look-image-overlay">
            <i class="fa fa-cloud-upload"></i>
            <br>
            <translate>Drop to upload</translate>
        </div>
        <div v-else class="document-quick-look-image-overlay-forbidden">
            <i class="fa fa-ban"></i>
            <br>
            <translate>You are not allowed to update this file</translate>
        </div>
        <img v-bind:src="item.file_properties.html_url" v-bind:alt="item.title">
    </div>
    <!-- eslint-disable-next-line vue/no-v-html -->
    <div v-html="escaped_embedded_content"
         class="document-quick-look-embedded"
         v-else-if="is_embedded"
    ></div>
    <div class="document-quick-look-icon" v-else>
        <i class="fa fa-icon" v-bind:class="iconClass"></i>
        <br>
        <span
            v-if="item.user_can_write"
            key="upload"
            class="document-quick-look-dropzone-text"
            v-translate
        >
            Drop to upload
        </span>
        <translate
            v-else-if="is_a_folder"
            key="folder"
            class="document-quick-look-dropzone-text-forbidden"
        >
            You are not allowed to write in this folder
        </translate>
        <translate
            v-else
            key="file"
            class="document-quick-look-dropzone-text-forbidden"
        >
            You are not allowed to update this file
        </translate>
    </div>
</template>
<script>
import dompurify from "dompurify";
import { TYPE_EMBEDDED, TYPE_FOLDER } from "../../../constants.js";

export default {
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
