<!--
  - Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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
        <h1 v-bind:class="title_class" class="document-header-title">
            {{ folder_title }}
        </h1>
        <div class="document-header-actions">
            <div class="tlp-dropdown" v-if="can_display_new_document_button">
                <div class="tlp-dropdown-split-button">
                    <new-item-button class="tlp-button-primary tlp-dropdown-split-button-main"/>
                    <dropdown-button/>
                </div>
                <new-item-modal/>
                <new-folder-modal/>
            </div>
            <div class="document-header-spacer"></div>
            <file-upload-manager/>
            <search-box v-if="can_display_search_box"/>
        </div>
    </div>
</template>

<script>
import { mapGetters, mapState } from "vuex";
import SearchBox from "./SearchBox.vue";
import NewItemButton from "./NewItem/NewItemButton.vue";
import NewItemModal from "./NewItem/NewItemModal.vue";
import DropdownButton from "./Dropdown/DropdownButton.vue";
import FileUploadManager from "./FilesUploads/FilesUploadsManager.vue";
import NewFolderModal from "./NewItem/NewFolderModal.vue";

export default {
    name: "FolderHeader",
    components: {
        NewFolderModal,
        DropdownButton,
        SearchBox,
        NewItemButton,
        NewItemModal,
        FileUploadManager
    },
    computed: {
        ...mapState(["is_loading_ascendant_hierarchy", "current_folder"]),
        ...mapGetters(["current_folder_title", "is_folder_empty"]),
        title_class() {
            return this.is_loading_ascendant_hierarchy
                ? "tlp-skeleton-text document-folder-title-loading"
                : "";
        },
        folder_title() {
            return this.is_loading_ascendant_hierarchy ? "" : this.current_folder_title;
        },
        can_display_search_box() {
            return this.current_folder && !this.is_folder_empty;
        },
        can_display_new_document_button() {
            return this.current_folder && this.current_folder.user_can_write;
        }
    }
};
</script>
