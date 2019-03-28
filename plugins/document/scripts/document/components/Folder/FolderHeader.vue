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
        <h1 class="document-header-title">
            <span v-bind:class="title_class">
                {{ folder_title }}
            </span>
        </h1>
        <div class="document-header-actions">
            <div class="tlp-dropdown" v-if="can_display_new_document_button">
                <div class="tlp-dropdown-split-button">
                    <new-item-button class="tlp-button-primary tlp-dropdown-split-button-main" v-bind:item="current_folder"/>
                    <dropdown-button>
                        <dropdown-menu-current-folder/>
                    </dropdown-button>
                </div>
                <new-item-modal/>
                <new-folder-modal/>
                <update-item-modal v-bind:is="shown_modal" v-bind:item="updated_item"/>
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
import DropdownMenuCurrentFolder from "./Dropdown/DropdownMenuCurrentFolder.vue";
import UpdateFileModal from "./UpdateItem/UpdateFileModal.vue";
import UpdateEmbeddedFileModal from "./UpdateItem/UpdateEmbeddedFileModal.vue";

export default {
    name: "FolderHeader",
    components: {
        UpdateEmbeddedFileModal,
        UpdateFileModal,
        DropdownMenuCurrentFolder,
        NewFolderModal,
        DropdownButton,
        SearchBox,
        NewItemButton,
        NewItemModal,
        FileUploadManager
    },
    data() {
        return {
            shown_modal: "",
            updated_item: null
        };
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
    },
    mounted() {
        document.addEventListener("show-update-file-modal", this.showUpdateFileModal);
        document.addEventListener(
            "show-update-embedded-file-modal",
            this.showUpdateEmbeddedFileModal
        );

        this.$once("hook:beforeDestroy", () => {
            document.removeEventListener("show-update-file-modal", this.showUpdateFileModal);
            document.removeEventListener(
                "show-update-embedded-file-modal",
                this.showUpdateEmbeddedFileModal
            );
        });
    },
    methods: {
        showUpdateFileModal(event) {
            this.updated_item = event.detail.current_item;
            this.shown_modal = () =>
                import(/* webpackChunkName: "document-update-file-modal" */ "./UpdateItem/UpdateFileModal.vue");
        },
        showUpdateEmbeddedFileModal(event) {
            this.updated_item = event.detail.current_item;
            this.shown_modal = () =>
                import(/* webpackChunkName: "document-update-embedded-file-modal" */ "./UpdateItem/UpdateEmbeddedFileModal.vue");
        }
    }
};
</script>
