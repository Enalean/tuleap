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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div>
        <permission-error v-if="has_folder_permission_error"/>
        <document-breadcrumb v-if="! has_folder_permission_error"/>
        <loading-error v-if="has_folder_loading_error"/>
        <router-view/>
    </div>
</template>
<script>
import { mapState } from "vuex";
import DocumentBreadcrumb from "./DocumentBreadcrumb.vue";
import PermissionError from "./Folder/empty-states/PermissionError.vue";
import LoadingError from "./Folder/empty-states/LoadingError.vue";

export default {
    name: "App",
    components: { DocumentBreadcrumb, PermissionError, LoadingError },
    props: {
        projectId: Number,
        projectName: String,
        userIsAdmin: Boolean,
        dateTimeFormat: String
    },
    computed: {
        ...mapState(["has_folder_permission_error", "has_folder_loading_error"])
    },
    created() {
        const base_title = document.title;
        this.$store.watch(
            state => state.current_folder_title,
            title => {
                if (title) {
                    document.title = title + " - " + base_title;
                }
            }
        );

        this.$store.commit("initApp", [
            this.projectId,
            this.projectName,
            this.userIsAdmin,
            this.dateTimeFormat
        ]);
    }
};
</script>
