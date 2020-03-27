<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
    <folder-container />
</template>

<script>
import FolderContainer from "./FolderContainer.vue";
import { mapState } from "vuex";

export default {
    name: "RootFolder",
    components: { FolderContainer },
    computed: {
        ...mapState(["current_folder"]),
    },
    mounted() {
        if (!this.current_folder || this.current_folder.parent_id !== 0) {
            this.$store.dispatch("loadRootFolder");
        }
        this.$store.commit("resetAscendantHierarchy");
        this.$store.dispatch("removeQuickLook");
    },
};
</script>
