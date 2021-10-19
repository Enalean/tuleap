<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <i
        class="fa fa-fw document-folder-toggle document-folder-content-fake-caret"
        v-if="can_be_displayed()"
    ></i>
</template>
<script lang="ts">
import { isFolder } from "../../../helpers/type-check-helper";
import { Component, Prop, Vue } from "vue-property-decorator";
import type { Folder, Item } from "../../../type";
import { State } from "vuex-class";

@Component
export default class FakeCaret extends Vue {
    @Prop({ required: true })
    readonly item!: Item;

    @State
    readonly current_folder!: Folder;

    @State
    readonly folder_content!: Array<Item>;

    is_item_in_current_folder(): boolean {
        return this.item.parent_id === this.current_folder.id;
    }
    is_item_sibling_of_a_folder(): boolean {
        return Boolean(
            this.folder_content.find(
                (item) => item.parent_id === this.current_folder.id && isFolder(item)
            )
        );
    }
    can_be_displayed(): boolean {
        return !this.is_item_in_current_folder() || this.is_item_sibling_of_a_folder();
    }
}
</script>
