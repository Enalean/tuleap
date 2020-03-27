<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <a
        href="#"
        v-on:click.prevent="showNewFolderModal"
        class="tlp-dropdown-menu-item"
        role="menuitem"
        v-if="is_item_a_folder(item) && item.user_can_write"
        data-test="document-new-folder-creation-button"
    >
        <i class="fa fa-fw fa-folder-open-o tlp-dropdown-menu-item-icon"></i>
        <translate>New folder</translate>
    </a>
</template>
<script>
import { mapGetters } from "vuex";
import EventBus from "../../../helpers/event-bus.js";
export default {
    name: "NewFolderSecondaryAction",
    props: { item: Object },
    computed: { ...mapGetters(["is_item_a_folder"]) },
    methods: {
        showNewFolderModal() {
            EventBus.$emit("show-new-folder-modal", { detail: { parent: this.item } });
        },
    },
};
</script>
