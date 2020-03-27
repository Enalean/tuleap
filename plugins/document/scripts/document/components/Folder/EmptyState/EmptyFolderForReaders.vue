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
            <empty-folder-for-readers-svg />
        </div>
        <div class="empty-page-text-with-small-text">
            <translate>This folder is empty</translate>
            <div class="empty-page-small-text" v-translate>
                or you don't have permissions to see its items
            </div>
        </div>
        <router-link
            v-bind:to="route_to"
            class="tlp-button-primary tlp-button-large"
            v-if="can_go_to_parent"
        >
            <i class="fa fa-reply tlp-button-icon"></i>
            <translate>Go to parent folder</translate>
        </router-link>
    </div>
</template>

<script>
import { mapState } from "vuex";
import EmptyFolderForReadersSvg from "../../svg/folder/EmptyFolderForReadersSvg.vue";

export default {
    name: "EmptyFolderForReaders",
    components: { EmptyFolderForReadersSvg },
    computed: {
        ...mapState(["current_folder", "current_folder_ascendant_hierarchy"]),
        index_of_parent() {
            return this.current_folder_ascendant_hierarchy.length - 2;
        },
        parent() {
            if (this.index_of_parent > 0) {
                return this.current_folder_ascendant_hierarchy[this.index_of_parent];
            }

            return null;
        },
        route_to() {
            return this.parent !== null
                ? { name: "folder", params: { item_id: this.parent.id } }
                : { name: "root_folder" };
        },
        can_go_to_parent() {
            return this.index_of_parent >= -1;
        },
    },
};
</script>
