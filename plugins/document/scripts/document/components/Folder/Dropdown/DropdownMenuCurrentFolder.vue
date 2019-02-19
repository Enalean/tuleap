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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <dropdown-menu v-bind:is-in-folder-empty-state="isInFolderEmptyState" v-bind:item="current_folder">
        <a href="#" v-on:click.prevent="showNewFolderModal" class="tlp-dropdown-menu-item" role="menuitem">
            <i class="fa fa-fw fa-folder-open-o tlp-dropdown-menu-item-icon"></i>
            <translate>New folder</translate>
        </a>
    </dropdown-menu>
</template>
<script>
import { mapState } from "vuex";
import DropdownMenu from "./DropdownMenu.vue";
export default {
    components: { DropdownMenu },
    props: {
        isInFolderEmptyState: Boolean
    },
    computed: {
        ...mapState(["current_folder"])
    },
    methods: {
        showNewFolderModal() {
            document.dispatchEvent(
                new CustomEvent("show-new-folder-modal", {
                    detail: { parent: this.current_folder }
                })
            );
        }
    }
};
</script>
