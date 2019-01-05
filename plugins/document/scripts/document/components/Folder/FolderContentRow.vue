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
    <tr v-bind:class="{ 'document-tree-item-hidden': is_folded, 'document-tree-item-created': item.created }">
        <td>
            <component
                v-bind:is="cell_title_component_name"
                v-bind:item="item"
                v-bind:style="item_indentation"
                class="document-folder-content-title"
            />
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
import { TYPE_FOLDER, TYPE_LINK, TYPE_FILE, TYPE_WIKI } from "../../constants.js";
import moment from "moment";
import phptomoment from "phptomoment";

export default {
    name: "FolderContentRow",
    components: { UserBadge },
    props: {
        item: Object
    },
    computed: {
        ...mapState(["date_time_format", "folded_items_ids"]),
        formatted_date() {
            return moment(this.item.last_update_date).fromNow();
        },
        formatted_full_date() {
            return moment(this.item.last_update_date).format(phptomoment(this.date_time_format));
        },
        is_folded() {
            return this.folded_items_ids.includes(this.item.id);
        },
        item_indentation() {
            const indentation_size = 10 + this.item.level * 23;

            return {
                "padding-left": `${indentation_size}px`
            };
        },
        cell_title_component_name() {
            let name = "Document";
            switch (this.item.type) {
                case TYPE_FOLDER:
                case TYPE_LINK:
                case TYPE_FILE:
                case TYPE_WIKI:
                    name = this.item.type;
                    name = name.charAt(0).toUpperCase() + name.slice(1);
                    break;
                default:
                    break;
            }
            return () =>
                import(/* webpackChunkName: "document-cell-title-" */ `./ItemTitle/${name}CellTitle.vue`);
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
