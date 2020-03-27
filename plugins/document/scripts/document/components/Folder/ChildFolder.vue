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
    <folder-container />
</template>

<script>
import { mapState } from "vuex";
import FolderContainer from "./FolderContainer.vue";

export default {
    name: "ChildFolder",
    components: { FolderContainer },
    computed: {
        ...mapState(["current_folder", "currently_previewed_item"]),
    },
    watch: {
        $route(to) {
            if (this.$route.name !== "preview") {
                this.$store.dispatch("removeQuickLook");
                if (this.current_folder && this.current_folder.id !== this.$route.params.item_id) {
                    this.$store.dispatch("loadFolder", parseInt(this.$route.params.item_id, 10));
                }
            } else {
                this.$store.dispatch("toggleQuickLook", to.params.preview_item_id);
            }
        },
    },
    async mounted() {
        if (this.$route.name === "preview") {
            await this.$store.dispatch(
                "toggleQuickLook",
                parseInt(this.$route.params.preview_item_id, 10)
            );

            if (!this.current_folder && this.currently_previewed_item) {
                this.$store.dispatch(
                    "loadFolder",
                    parseInt(this.currently_previewed_item.parent_id, 10)
                );
            }
        } else {
            this.$store.dispatch("loadFolder", parseInt(this.$route.params.item_id, 10));
            this.$store.dispatch("removeQuickLook");
        }
    },
};
</script>
