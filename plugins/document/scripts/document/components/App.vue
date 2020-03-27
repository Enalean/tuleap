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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="document-app">
        <document-breadcrumb v-if="!has_folder_permission_error" />

        <permission-error v-if="has_folder_permission_error" />
        <item-permission-error
            v-else-if="has_document_permission_error"
            v-bind:csrf_token="csrf_token"
            v-bind:csrf_token_name="csrf_token_name"
        />
        <loading-error
            v-else-if="
                has_folder_loading_error || has_document_loading_error || has_document_lock_error
            "
        />
        <router-view v-else />
        <switch-to-old-u-i v-if="user_id !== 0" />
        <post-item-deletion-notification />
    </div>
</template>
<script>
import { mapGetters, mapState } from "vuex";
import DocumentBreadcrumb from "./Breadcrumb/DocumentBreadcrumb.vue";
import PermissionError from "./Folder/Error/PermissionError.vue";
import ItemPermissionError from "./Folder/Error/ItemPermissionError.vue";
import LoadingError from "./Folder/Error/LoadingError.vue";
import SwitchToOldUI from "./Folder/SwitchToOldUI.vue";
import PostItemDeletionNotification from "./Folder/ModalDeleteItem/PostItemDeletionNotification.vue";

export default {
    name: "App",
    components: {
        DocumentBreadcrumb,
        PermissionError,
        LoadingError,
        SwitchToOldUI,
        ItemPermissionError,
        PostItemDeletionNotification,
    },
    props: {
        user_id: Number,
        project_id: Number,
        user_is_admin: Boolean,
        user_can_create_wiki: Boolean,
        date_time_format: String,
        max_files_dragndrop: Number,
        max_size_upload: Number,
        embedded_are_allowed: Boolean,
        is_deletion_allowed: Boolean,
        is_item_status_metadata_used: Boolean,
        is_obsolescence_date_metadata_used: Boolean,
        csrf_token: String,
        csrf_token_name: String,
    },
    computed: {
        ...mapState("error", [
            "has_folder_permission_error",
            "has_folder_loading_error",
            "has_document_permission_error",
            "has_document_loading_error",
            "has_document_lock_error",
        ]),
        ...mapGetters(["is_uploading"]),
    },
    created() {
        const base_title = document.title;
        this.$store.watch(
            (state, getters) => getters.current_folder_title,
            (title, old_title) => {
                if (title) {
                    document.title = title + " - " + base_title;
                } else if (old_title) {
                    document.title = base_title;
                }
            }
        );

        this.$store.commit("initApp", [
            this.user_id,
            this.project_id,
            this.user_is_admin,
            this.date_time_format,
            this.$gettext("Documents"),
            this.user_can_create_wiki,
            this.max_files_dragndrop,
            this.max_size_upload,
            this.embedded_are_allowed,
            this.is_deletion_allowed,
            this.is_item_status_metadata_used,
            this.is_obsolescence_date_metadata_used,
        ]);

        window.addEventListener("beforeunload", (event) => {
            if (this.is_uploading) {
                event.returnValue = true;
                event.preventDefault();
            }
        });
    },
};
</script>
