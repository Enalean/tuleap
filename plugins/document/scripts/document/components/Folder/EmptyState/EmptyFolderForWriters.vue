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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <section
        class="empty-state-page document-empty-folder-for-writers"
        data-test="document-empty-state"
    >
        <div class="empty-state-illustration">
            <empty-folder-for-writers-svg />
        </div>
        <h1 class="empty-state-title" v-translate>It's time to add new documents!</h1>
        <div class="empty-state-action tlp-dropdown">
            <div class="tlp-dropdown-split-button">
                <new-item-button
                    class="tlp-button-primary tlp-dropdown-split-button-main"
                    v-bind:item="current_folder"
                />
                <drop-down-button
                    v-bind:is-in-large-mode="false"
                    v-bind:is-in-quick-look-mode="false"
                    v-bind:is-appended="true"
                >
                    <drop-down-current-folder v-bind:is-in-folder-empty-state="true" />
                </drop-down-button>
            </div>
        </div>
    </section>
</template>

<script lang="ts">
import EmptyFolderForWritersSvg from "../../svg/folder/EmptyFolderForWritersSvg.vue";
import NewItemButton from "../ActionsButton/NewItemButton.vue";
import DropDownButton from "../DropDown/DropDownButton.vue";
import DropDownCurrentFolder from "../DropDown/DropDownCurrentFolder.vue";
import { Component, Vue } from "vue-property-decorator";
import { State } from "vuex-class";
import type { Folder } from "../../../type";

@Component({
    components: { DropDownCurrentFolder, EmptyFolderForWritersSvg, NewItemButton, DropDownButton },
})
export default class EmptyFolderForWriters extends Vue {
    @State
    readonly current_folder!: Folder;
}
</script>
