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
    <button
        v-on:click.prevent="showNewFolderModal"
        class="tlp-dropdown-menu-item"
        type="button"
        role="menuitem"
        v-if="is_item_a_folder(item) && item.user_can_write"
        data-test="document-new-folder-creation-button"
        data-shortcut-create-folder
    >
        <i class="far fa-fw fa-folder-open tlp-dropdown-menu-item-icon"></i>
        <translate>New folder</translate>
    </button>
</template>
<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import emitter from "../../../helpers/emitter";
import { isFolder } from "../../../helpers/type-check-helper";
import type { Item } from "../../../type";

@Component
export default class NewFolderSecondaryAction extends Vue {
    @Prop({ required: true })
    readonly item!: Item;

    showNewFolderModal(): void {
        emitter.emit("show-new-folder-modal", { detail: { parent: this.item } });
    }
    is_item_a_folder(item: Item): boolean {
        return isFolder(item);
    }
}
</script>
