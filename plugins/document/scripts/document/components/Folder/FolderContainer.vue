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
    <div class="tlp-framed" v-bind:class="classes" v-if="!does_folder_have_any_error">
        <folder-view />
    </div>
</template>
<script>
import { mapGetters, mapState } from "vuex";
import FolderView from "./FolderView.vue";

export default {
    name: "FolderContainer",
    components: { FolderView },
    computed: {
        ...mapState(["is_loading_folder"]),
        ...mapGetters(["is_folder_empty"]),
        ...mapGetters("error", ["does_folder_have_any_error"]),
        classes() {
            return {
                "document-folder-container-empty": !this.is_loading_folder && this.is_folder_empty,
            };
        },
    },
};
</script>
