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
    <div class="empty-state-page">
        <div class="empty-state-illustration">
            <empty-folder-for-readers-svg />
        </div>
        <div class="empty-state-text-with-small-text">
            <translate>This folder is empty</translate>
            <div class="empty-state-text-small" v-translate>
                or you don't have permissions to see its items
            </div>
        </div>
        <router-link
            v-bind:to="route_to"
            class="tlp-button-primary tlp-button-large"
            v-if="can_go_to_parent"
        >
            <i class="fa fa-reply tlp-button-icon"></i>
            <translate>Go to parent folder</translate>
        </router-link>
    </div>
</template>

<script lang="ts">
import EmptyFolderForReadersSvg from "../../svg/folder/EmptyFolderForReadersSvg.vue";
import { Component, Vue } from "vue-property-decorator";
import { State } from "vuex-class";
import type { Folder, Item } from "../../../type";

interface RouterPayload {
    name: string;
    params?: {
        item_id?: number;
    };
}

@Component({
    components: { EmptyFolderForReadersSvg },
})
export default class EmptyFolderForReaders extends Vue {
    @State
    readonly current_folder!: Folder;

    @State
    readonly current_folder_ascendant_hierarchy!: Array<Folder>;

    index_of_parent(): number {
        return this.current_folder_ascendant_hierarchy.length - 2;
    }
    parent(): Item | null {
        if (this.index_of_parent() > 0) {
            return this.current_folder_ascendant_hierarchy[this.index_of_parent()];
        }

        return null;
    }
    route_to(): RouterPayload {
        const parent = this.parent();
        return parent !== null
            ? { name: "folder", params: { item_id: parent.id } }
            : { name: "root_folder" };
    }
    can_go_to_parent(): boolean {
        return this.index_of_parent() >= -1;
    }
}
</script>
