<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <div class="document-header">
        <h1 v-bind:class="title_class" class="document-header-title">{{ folder_title }}</h1>
        <div class="document-header-actions">
            <div class="document-header-spacer"></div>
            <search-box v-if="is_loaded_with_content" v-bind:folder_id="folder_id"/>
        </div>
    </div>
</template>

<script>
import { mapGetters, mapState } from "vuex";
import SearchBox from "./SearchBox.vue";

export default {
    name: "FolderHeader",
    components: { SearchBox },
    props: {
        folder_id: Number
    },

    computed: {
        ...mapState(["is_loading_ascendant_hierarchy"]),
        ...mapGetters(["current_folder_title", "is_folder_empty"]),
        title_class() {
            return this.is_loading_ascendant_hierarchy
                ? "tlp-skeleton-text document-folder-title-loading"
                : "";
        },
        folder_title() {
            return this.is_loading_ascendant_hierarchy ? "" : this.current_folder_title;
        },
        is_loaded_with_content() {
            return !this.is_folder_empty;
        }
    }
};
</script>
