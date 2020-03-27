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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="document-switch-to-docman">
        <a
            v-bind:href="redirect_url"
            v-on:click.prevent="redirectUser()"
            class="document-switch-to-docman-link"
            data-test="document-switch-to-old-ui"
        >
            <i class="fa fa-random document-switch-to-docman-icon"></i>
            <!--
            -->
            <translate>Switch to old user interface</translate>
        </a>
    </div>
</template>

<script>
import { mapState } from "vuex";
import { redirectToUrl } from "../../helpers/location-helper.js";

export default {
    name: "SwitchToOldUI",
    computed: {
        ...mapState(["project_id", "current_folder"]),
        redirect_url() {
            const encoded_project_id = encodeURIComponent(this.project_id);
            if (this.$route.name === "folder") {
                return (
                    "/plugins/docman/?group_id=" +
                    encoded_project_id +
                    "&action=show&id=" +
                    encodeURIComponent(parseInt(this.$route.params.item_id, 10))
                );
            } else if (this.$route.name === "preview" && this.current_folder) {
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
    methods: {
        async redirectUser() {
            await this.$store.dispatch("setUserPreferenciesForUI");
            redirectToUrl(this.redirect_url);
        },
    },
};
</script>
