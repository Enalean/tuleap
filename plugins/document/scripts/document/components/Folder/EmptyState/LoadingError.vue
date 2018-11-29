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
    <div class="empty-page">
        <div class="empty-page-illustration">
            <loading-error-svg/>
        </div>
        <div class="empty-page-text-with-small-text">
            <translate>Oops, there's an issue.</translate>
            <div class="empty-page-small-text" v-translate>It seems the contents of this folder can't be loaded.</div>
            <template v-if="has_folder_loading_error">
                <div class="document-folder-error-link">
                    <a v-if="! is_more_shown"
                       v-on:click="is_more_shown = true"
                       href="javascript:void(0)"
                       v-translate
                    >Show error details</a>
                </div>
                <pre v-if="is_more_shown"
                     class="document-folder-error-details"
                >{{ folder_loading_error }}</pre>
            </template>
        </div>
        <router-link
            class="tlp-button-primary tlp-button-large"
            v-bind:to="{ name: 'root_folder' }"
            v-if="can_go_to_root"
        >
            <i class="fa fa-reply tlp-button-icon"></i><translate>Go to parent folder</translate>
        </router-link>
    </div>
</template>

<script>
import { mapState } from "vuex";
import LoadingErrorSvg from "./LoadingErrorSvg.vue";

export default {
    name: "LoadingError",
    components: { LoadingErrorSvg },
    data() {
        return {
            is_more_shown: false
        };
    },
    computed: {
        ...mapState(["folder_loading_error", "has_folder_loading_error"]),
        can_go_to_root() {
            return this.$route.name !== "root_folder";
        }
    }
};
</script>
