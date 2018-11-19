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
        <h1 v-translate>Documents</h1>
        <folder-loading-screen v-if="is_loading_folder"/>
        <div class="tlp-card" v-if="has_loaded_without_error_and_is_empty">
            <div class="empty-pane">
                <div class="empty-page-illustration">
                    <empty-folder-svg/>
                </div>
                <p class="empty-page-text" v-translate>It's time to add new documents!</p>
                <button type="button" class="tlp-button-primary tlp-button-large" disabled>
                    <i class="fa fa-plus tlp-button-icon"></i>
                    <translate>New document</translate>
                </button>
            </div>
        </div>
        <div class="tlp-card" v-if="! is_folder_empty">
            <folder-content/>
        </div>
    </div>
</template>
<script>
import { mapState, mapGetters } from "vuex";

import FolderLoadingScreen from "./Folder/FolderLoadingScreen.vue";
import EmptyFolderSvg from "./Folder/EmptyFolderSvg.vue";
import FolderContent from "./Folder/FolderContent.vue";

export default {
    name: "FolderView",
    components: { FolderLoadingScreen, EmptyFolderSvg, FolderContent },
    computed: {
        ...mapState({
            has_loaded_without_error_and_is_empty: (state, getters) =>
                !state.is_loading_folder && !getters.has_error && getters.is_folder_empty,
            is_loading_folder: state => state.is_loading_folder
        }),
        ...mapGetters(["is_folder_empty"])
    }
};
</script>
