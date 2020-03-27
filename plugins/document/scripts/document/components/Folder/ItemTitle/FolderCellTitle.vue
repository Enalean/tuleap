<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
    <div>
        <i
            class="document-folder-icon-color fa fa-fw document-folder-toggle document-folder-content-icon"
            v-bind:class="{ 'fa-caret-down': !is_closed, 'fa-caret-right': is_closed }"
            v-on:click="toggle"
            data-test="toggle"
        ></i>
        <i
            class="document-folder-icon-color fa fa-fw document-folder-content-icon"
            data-test="document-folder-icon-open"
            v-bind:class="{
                'fa-folder': is_closed,
                'fa-folder-open': is_folder_open,
                'fa-circle-o-notch fa-spin': is_loading,
            }"
        ></i>
        <a
            v-on:click.prevent="goToFolder"
            v-bind:href="folder_href"
            class="document-folder-subitem-link"
            data-test="document-go-to-folder-link"
        >
            {{ title }}
        </a>
    </div>
</template>

<script>
import { mapState, mapGetters } from "vuex";
import { abortCurrentUploads } from "../../../helpers/abort-current-uploads.js";
import { getTitleWithElipsisIfNeeded } from "../../../helpers/cell-title-formatter.js";

export default {
    name: "FolderCellTitle",
    props: {
        item: Object,
    },
    data() {
        return {
            is_closed: true,
            is_loading: false,
            have_children_been_loaded: false,
        };
    },
    computed: {
        ...mapState(["folder_content", "files_uploads_list"]),
        ...mapGetters(["is_uploading"]),
        folder_href() {
            const { href } = this.$router.resolve({
                name: "folder",
                params: { item_id: this.item.id },
            });

            return href;
        },
        is_folder_open() {
            return !this.is_loading && !this.is_closed;
        },
        is_folded() {
            return this.folded_items_ids.includes(this.item.id);
        },
        title() {
            return getTitleWithElipsisIfNeeded(this.item);
        },
        has_uploading_content() {
            const uploading_content = this.files_uploads_list.find(
                (file) => file.parent_id === this.item.id && file.progress > 0
            );

            return uploading_content;
        },
    },
    mounted() {
        this.$store.commit("initializeFolderProperties", this.item);
        if (this.item.is_expanded !== false) {
            this.open();
        }
    },
    methods: {
        async goToFolder() {
            if (!this.is_uploading || abortCurrentUploads(this.$gettext, this.$store)) {
                await this.doGoToFolder();
            }
        },
        async doGoToFolder() {
            this.$store.commit("appendFolderToAscendantHierarchy", this.item);
            await this.$router.push({ name: "folder", params: { item_id: this.item.id } });
        },
        async loadChildren() {
            this.is_loading = true;

            await this.$store.dispatch("getSubfolderContent", this.item.id);

            this.is_loading = false;
            this.have_children_been_loaded = true;
        },
        open() {
            if (!this.have_children_been_loaded) {
                this.loadChildren();
            }

            this.is_closed = false;

            this.$store.commit("unfoldFolderContent", this.item.id);
        },
        toggle() {
            if (this.is_closed) {
                this.$store.commit("toggleCollapsedFolderHasUploadingContent", [this.item, false]);
                this.open();
            } else {
                this.$store.commit("foldFolderContent", this.item.id);
                this.$store.commit("toggleCollapsedFolderHasUploadingContent", [
                    this.item,
                    this.has_uploading_content,
                ]);

                this.is_closed = true;
            }

            this.$store.dispatch("setUserPreferenciesForFolder", [this.item.id, this.is_closed]);
        },
    },
};
</script>
