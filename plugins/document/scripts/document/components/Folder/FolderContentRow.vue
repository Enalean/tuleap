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
    <tr>
        <folder-cell-title v-bind:item="item" v-if="is_folder"/>
        <link-cell-title v-bind:item="item" v-else-if="is_link"/>
        <file-cell-title v-bind:item="item" v-else-if="is_file"/>
        <document-cell-title v-bind:item="item" v-else/>

        <td class="document-tree-cell-owner"><user-badge v-bind:user="item.owner"/></td>
        <td class="document-tree-cell-updatedate tlp-tooltip tlp-tooltip-left" v-bind:data-tlp-tooltip="formatted_full_date">
            {{ formatted_date }}
        </td>
    </tr>
</template>

<script>
import { mapState } from "vuex";
import UserBadge from "../User/UserBadge.vue";
import { TYPE_FOLDER, TYPE_LINK, TYPE_FILE } from "../../constants.js";
import FolderCellTitle from "./ItemTitle/FolderCellTitle.vue";
import DocumentCellTitle from "./ItemTitle/DocumentCellTitle.vue";
import LinkCellTitle from "./ItemTitle/LinkCellTitle.vue";
import FileCellTitle from "./ItemTitle/FileCellTitle.vue";
import moment from "moment";
import phptomoment from "phptomoment";

export default {
    name: "FolderContentRow",
    components: { UserBadge, FolderCellTitle, DocumentCellTitle, LinkCellTitle, FileCellTitle },
    props: {
        item: Object
    },
    computed: {
        ...mapState(["date_time_format"]),
        formatted_date() {
            return moment(this.item.last_update_date).fromNow();
        },
        formatted_full_date() {
            return moment(this.item.last_update_date).format(phptomoment(this.date_time_format));
        },
        is_folder() {
            return this.item.type === TYPE_FOLDER;
        },
        is_link() {
            return this.item.type === TYPE_LINK;
        },
        is_file() {
            return this.item.type === TYPE_FILE;
        }
    },
    methods: {
        goToFolder(event) {
            event.preventDefault();
            this.$store.commit("setCurrentFolder", this.item);
            this.$store.commit("appendFolderToAscendantHierarchy", this.item);
            this.$router.push({ name: "folder", params: { item_id: this.item.id } });
        }
    }
};
</script>
