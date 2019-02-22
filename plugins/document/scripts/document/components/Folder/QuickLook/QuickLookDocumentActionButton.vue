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
        <div class="tlp-dropdown-split-button">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-button-small tlp-dropdown-split-button-main"
                v-on:click="goToUpdate"
                v-if="item.user_can_write"
            >
                <i class="fa fa-mail-forward tlp-button-icon"></i>
                <translate>Update</translate>
            </button>

            <button
                class="tlp-button-primary tlp-button-outline tlp-button-small tlp-dropdown-split-button-main"
                type="button"
                v-on:click="toggleNeighborDropdown"
                v-else
            >
                <i class="fa fa-ellipsis-h"></i>
            </button>
            <dropdown-button ref="dropdown_button" v-bind:is-in-quick-look-mode="true">
                <dropdown-menu
                    v-bind:item="item"
                    v-bind:is-in-quick-look-mode="true"
                    v-bind:hide-item-title="true"
                    v-bind:hide-details-entry="isDetailsButtonShown"
                />
            </dropdown-button>
        </div>
    </div>
</template>

<script>
import { mapState } from "vuex";
import DropdownButton from "../Dropdown/DropdownButton.vue";
import DropdownMenu from "../Dropdown/DropdownMenu.vue";
import { TYPE_EMPTY, TYPE_WIKI } from "../../../constants.js";

export default {
    components: { DropdownButton, DropdownMenu },
    props: {
        item: Object,
        isDetailsButtonShown: Boolean
    },
    computed: {
        ...mapState(["project_id"])
    },
    methods: {
        goToUpdate() {
            const action =
                this.item.type !== TYPE_WIKI && this.item.type !== TYPE_EMPTY
                    ? "action_new_version"
                    : "action_update";

            window.location.assign(
                `/plugins/docman/index.php?group_id=${this.project_id}&id=${
                    this.item.id
                }&action=${action}`
            );
        },
        toggleNeighborDropdown(event) {
            event.stopPropagation();
            this.$refs.dropdown_button.toggleDropdown();
        }
    }
};
</script>
