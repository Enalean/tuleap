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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <div class="document-quick-look-folder-action">
        <button type="button" class="tlp-button-primary tlp-button-small document-quick-look-folder-action-new-folder-button" v-on:click.prevent="showNewFolderModal">
            <i class="fa fa-folder-open-o tlp-button-icon"></i> <translate> New folder </translate>
        </button>
        <new-item-button class="tlp-button-primary tlp-button-small tlp-button-outline" v-bind:item="item"/>
    </div>
</template>

<script>
import NewItemButton from "../NewItem/NewItemButton.vue";

export default {
    name: "QuickLookFileProperties",
    components: { NewItemButton },
    props: {
        item: Object
    },
    methods: {
        showNewFolderModal() {
            document.dispatchEvent(
                new CustomEvent("show-new-folder-modal", {
                    detail: { parent: this.item }
                })
            );
        }
    }
};
</script>
