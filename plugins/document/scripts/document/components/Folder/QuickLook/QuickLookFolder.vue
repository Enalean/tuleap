<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
  -->

<template>
    <div class="document-quick-look-document-action">
        <button
            v-if="item.user_can_write"
            type="button"
            class="tlp-button-primary tlp-button-small document-quick-look-folder-action-new-folder-button"
            v-on:click.prevent="showNewFolderModal"
        >
            <i class="fa fa-folder-open-o tlp-button-icon"></i>
            <translate>New folder</translate>
        </button>
        <drop-down-quick-look v-bind:item="item" />
        <template v-if="can_delete_folder">
            <div class="document-header-spacer"></div>
            <quick-look-delete-button v-bind:item="item" />
        </template>
    </div>
</template>

<script>
import QuickLookDeleteButton from "../ActionsQuickLookButton/QuickLookDeleteButton.vue";
import DropDownQuickLook from "../DropDown/DropDownQuickLook.vue";
import EventBus from "../../../helpers/event-bus.js";

export default {
    components: { QuickLookDeleteButton, DropDownQuickLook },
    props: {
        item: Object,
    },
    computed: {
        can_delete_folder() {
            return this.item.user_can_write;
        },
    },
    methods: {
        showNewFolderModal() {
            EventBus.$emit("show-new-folder-modal", { detail: { parent: this.item } });
        },
    },
};
</script>
