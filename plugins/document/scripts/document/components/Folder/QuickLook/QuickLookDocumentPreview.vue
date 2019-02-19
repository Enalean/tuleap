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
        <img v-bind:src="item.file_properties.html_url" v-bind:alt="item.title">
    </div>
    <!-- eslint-disable-next-line vue/no-v-html -->
    <div v-html="escaped_embedded_content"
         class="document-quick-look-embedded"
         v-else-if="is_embedded"
    ></div>
    <div class="document-quick-look-icon" v-else>
        <i class="fa" v-bind:class="iconClass"></i>
    </div>
</template>
<script>
import dompurify from "dompurify";
import { TYPE_EMBEDDED } from "../../../constants.js";

export default {
    props: {
        item: Object,
        iconClass: String
    },
    computed: {
        is_an_image() {
            return (
                this.item.file_properties && this.item.file_properties.file_type.includes("image")
            );
        },
        is_embedded() {
            return this.item.type === TYPE_EMBEDDED;
        },
        escaped_embedded_content() {
            return dompurify.sanitize(this.item.embedded_file_properties.content);
        }
    }
};
</script>
