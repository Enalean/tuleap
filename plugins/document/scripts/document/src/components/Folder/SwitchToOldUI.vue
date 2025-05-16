<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <div class="document-switch-to-docman">
        <a
            v-bind:href="redirect_url"
            class="document-switch-to-docman-link"
            data-test="document-switch-to-old-ui"
        >
            <i class="fa-solid fa-shuffle document-switch-to-docman-icon"></i>
            <!--
            -->
            {{ $gettext("Switch to old user interface") }}
        </a>
    </div>
</template>

<script>
import { mapState } from "vuex";
import { useRoute } from "vue-router";

export default {
    name: "SwitchToOldUI",
    computed: {
        ...mapState(["current_folder"]),
        ...mapState("configuration", ["project_id"]),
        redirect_url() {
            const route = useRoute();
            const encoded_project_id = encodeURIComponent(this.project_id);
            if (route.name === "folder") {
                return (
                    "/plugins/docman/?group_id=" +
                    encoded_project_id +
                    "&action=show&id=" +
                    encodeURIComponent(parseInt(this.$route.params.item_id, 10))
                );
            } else if (route.name === "preview" && this.current_folder) {
                return (
                    "/plugins/docman/?group_id=" +
                    encoded_project_id +
                    "&action=show&id=" +
                    encodeURIComponent(parseInt(this.current_folder.id, 10))
                );
            }
            return "/plugins/docman/?group_id=" + encoded_project_id;
        },
    },
};
</script>
