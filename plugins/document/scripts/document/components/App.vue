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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="document-app">
        <permission-error v-if="has_folder_permission_error"/>
        <document-breadcrumb v-if="! has_folder_permission_error"/>
        <loading-error v-if="has_folder_loading_error"/>
        <router-view/>
        <switch-to-old-u-i/>
    </div>
</template>
<script>
import { mapState } from "vuex";
import DocumentBreadcrumb from "./Breadcrumb/DocumentBreadcrumb.vue";
import PermissionError from "./Folder/EmptyState/PermissionError.vue";
import LoadingError from "./Folder/EmptyState/LoadingError.vue";
import SwitchToOldUI from "./Folder/SwitchToOldUI.vue";

export default {
    name: "App",
    components: {
        DocumentBreadcrumb,
        PermissionError,
        LoadingError,
        SwitchToOldUI
    },
    props: {
        user_id: Number,
        project_id: Number,
        user_is_admin: Boolean,
        user_can_create_wiki: Boolean,
        date_time_format: String,
        max_files_dragndrop: Number,
        max_size_upload: Number,
        is_under_construction: Boolean
    },
    computed: {
        ...mapState(["has_folder_permission_error", "has_folder_loading_error"])
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
            this.is_under_construction
        ]);
    }
};
</script>
