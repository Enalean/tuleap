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
        <td v-if="is_folder">
            <i class="document-folder-icon-color fa fa-fw fa-folder"></i>
            <a v-on:click="goToFolder" v-bind:href="folder_href" class="document-folder-subitem-link">
                {{ item.title }}
            </a>
        </td>
        <td v-else>
            <i class="fa fa-fw " v-bind:class="icon_class"></i>
            {{ item.title }}
        </td>
        <td class="document-tree-cell-owner"><user-badge v-bind:user="item.owner"/></td>
        <td class="document-tree-cell-updatedate tlp-tooltip tlp-tooltip-left" v-bind:data-tlp-tooltip="formatted_full_date">
            {{ formatted_date }}
        </td>
    </tr>
</template>

<script>
import { mapState } from "vuex";
import UserBadge from "../User/UserBadge.vue";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI
} from "../../constants.js";
import moment from "moment";
import phptomoment from "phptomoment";
import { iconForMimeType } from "../../helpers/icon-for-mime-type";

export default {
    name: "FolderContentRow",
    components: { UserBadge },
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
        icon_class() {
            switch (this.item.type) {
                case TYPE_FOLDER:
                    return "fa-folder document-folder-icon";
                case TYPE_EMBEDDED:
                    return "fa-file-text document-text-icon";
                case TYPE_FILE:
                    return iconForMimeType(this.item);
                case TYPE_WIKI:
                    return "fa-wikipedia-w document-wiki-icon";
                case TYPE_LINK:
                    return "fa-link document-link-icon";
                case TYPE_EMPTY:
                default:
                    return "fa-file-o document-empty-icon";
            }
        },
        folder_href() {
            const { href } = this.$router.resolve({
                name: "folder",
                params: { item_id: this.item.id }
            });

            return href;
        }
    },
    methods: {
        goToFolder(event) {
            event.preventDefault();
            this.$store.commit("appendFolderToAscendantHierarchy", this.item);
            this.$router.push({ name: "folder", params: { item_id: this.item.id } });
        }
    }
};
</script>
