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
        <folder-header v-bind:folder_id="folder_id"/>
        <folder-loading-screen v-if="is_loading_folder"/>
        <div class="tlp-card" v-else>
            <empty-folder v-if="is_folder_empty"/>
            <folder-content v-else/>
        </div>
    </div>
</template>
<script>
import { mapState, mapGetters } from "vuex";

import FolderHeader from "./FolderHeader.vue";
import FolderLoadingScreen from "./FolderLoadingScreen.vue";
import FolderContent from "./FolderContent.vue";
import EmptyFolder from "./EmptyState/EmptyFolder.vue";

export default {
    name: "FolderView",
    components: {
        FolderHeader,
        EmptyFolder,
        FolderLoadingScreen,
        FolderContent
    },
    props: {
        folder_id: Number
    },
    computed: {
        ...mapState(["is_loading_folder"]),
        ...mapGetters(["is_folder_empty"])
    }
};
</script>
