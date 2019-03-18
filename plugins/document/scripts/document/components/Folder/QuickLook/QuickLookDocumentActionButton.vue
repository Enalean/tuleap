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
            <update-button v-bind:item="item"
                           v-bind:button-classes="button_classes"
                           v-bind:icon-classes="icon_classes"
            />
            <dropdown-button
                v-bind:is-in-quick-look-mode="true"
                v-bind:is-appended="item.user_can_write"
            >
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
import DropdownButton from "../Dropdown/DropdownButton.vue";
import DropdownMenu from "../Dropdown/DropdownMenu.vue";
import UpdateButton from "../UpdateItem/UpdateButton.vue";

export default {
    components: { UpdateButton, DropdownButton, DropdownMenu },
    props: {
        item: Object,
        isDetailsButtonShown: Boolean
    },
    computed: {
        button_classes() {
            return "tlp-button-primary tlp-button-outline tlp-button-small tlp-dropdown-split-button-main";
        },
        icon_classes() {
            return "fa fa-mail-forward tlp-button-icon";
        }
    }
};
</script>
