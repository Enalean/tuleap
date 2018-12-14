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
    <td>
        <i class="document-folder-icon-color fa fa-fw document-folder-toggle"
           v-bind:class="{ 'fa-caret-down': !is_closed, 'fa-caret-right': is_closed }"
           v-on:click="toggle"
        ></i>
        <i class="document-folder-icon-color fa fa-fw"
           v-bind:class="{
               'fa-folder': is_closed,
               'fa-folder-open': is_folder_open,
               'fa-spinner fa-spin': is_loading
           }"
        ></i>
        <a v-on:click="goToFolder" v-bind:href="folder_href" class="document-folder-subitem-link">
            {{ item.title }}
        </a>
    </td>
</template>

<script>
import { mapState } from "vuex";

export default {
    name: "FolderCellTitle",
    props: {
        item: Object
    },
    data() {
        return {
            is_closed: true,
            is_loading: false,
            have_children_been_loaded: false
        };
    },
    computed: {
        ...mapState(["folder_content"]),
        folder_href() {
            const { href } = this.$router.resolve({
                name: "folder",
                params: { item_id: this.item.id }
            });

            return href;
        },
        is_folder_open() {
            return !this.is_loading && !this.is_closed;
        },
        is_folded() {
            return this.folded_items_ids.includes(this.item.id);
        }
    },
    async mounted() {
        const user_preferences = await this.$store.dispatch("getFolderShouldBeOpen", this.item.id);

        if (user_preferences.value !== false) {
            this.open();
        }
    },
    methods: {
        goToFolder(event) {
            event.preventDefault();
            this.$store.commit("appendFolderToAscendantHierarchy", this.item);
            this.$router.push({ name: "folder", params: { item_id: this.item.id } });
        },
        async loadChildren() {
            this.is_loading = true;

            await this.$store.dispatch("getSubfolderContent", this.item.id);

            this.is_loading = false;
            this.have_children_been_loaded = true;
        },
        open() {
            if (!this.have_children_been_loaded) {
                this.loadChildren();
            }

            this.is_closed = false;

            this.$store.commit("unfoldFolderContent", this.item.id);
        },
        toggle() {
            if (this.is_closed) {
                this.open();
            } else {
                this.$store.commit("foldFolderContent", this.item.id);
                this.is_closed = true;
            }

            this.$store.dispatch("setUserPreferenciesForFolder", [this.item.id, this.is_closed]);
        }
    }
};
</script>
