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
        <a v-bind:href="redirect_url" v-on:click="redirectUser()" class="document-switch-to-docman-link">
            <i class="fa fa-random document-switch-to-docman-icon"></i><!--
            --><translate>Switch to old user interface</translate>
        </a>
    </div>
</template>

<script>
import { mapState } from "vuex";

export default {
    name: "SwitchToOldUI",
    computed: {
        ...mapState(["project_id", "current_folder"]),
        redirect_url() {
            if (this.$route.params.item_id) {
                return (
                    "/plugins/docman/?group_id=" +
                    this.project_id +
                    "&action=show&id=" +
                    parseInt(this.$route.params.item_id, 10)
                );
            }

            return "/plugins/docman/?group_id=" + this.project_id;
        }
    },
    methods: {
        redirectUser() {
            this.$store.dispatch("setUserPreferenciesForUI").then(() => {
                window.location = this.redirect_url();
            });
        }
    }
};
</script>
