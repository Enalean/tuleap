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
        <h1 v-bind:class="title_class">{{ folder_title }}</h1>
        <folder-loading-screen v-if="is_loading_folder"/>
        <div class="tlp-card" v-else>
            <empty-folder v-if="is_folder_empty"/>
            <folder-content v-else/>
        </div>
    </div>
</template>
<script>
import { mapState, mapGetters } from "vuex";

import FolderLoadingScreen from "./Folder/FolderLoadingScreen.vue";
import FolderContent from "./Folder/FolderContent.vue";
import EmptyFolder from "./Folder/empty-states/EmptyFolder.vue";

export default {
    name: "FolderView",
    components: {
        EmptyFolder,
        FolderLoadingScreen,
        FolderContent
    },
    computed: {
        ...mapState(["is_loading_folder", "is_loading_folder_title"]),
        ...mapGetters(["is_folder_empty", "current_folder_title"]),
        title_class() {
            return this.is_loading_folder_title
                ? "tlp-skeleton-text document-folder-title-loading"
                : "";
        },
        folder_title() {
            return this.is_loading_folder_title ? "" : this.current_folder_title;
        }
    }
};
</script>
