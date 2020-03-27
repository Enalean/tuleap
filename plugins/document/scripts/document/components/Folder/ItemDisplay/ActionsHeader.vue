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
    <div class="document-header-actions">
        <div class="tlp-dropdown document-dropdown-menu-button">
            <div class="tlp-dropdown-split-button">
                <create-new-item-version-button
                    class="tlp-button-primary tlp-dropdown-split-button-main"
                    v-bind:item="item"
                    v-bind:icon-classes="'fa fa-mail-forward tlp-button-icon'"
                    data-test="item-action-create-new-version-button"
                    v-if="item.user_can_write"
                />
                <details-item-button
                    v-bind:item="item"
                    v-bind:button-class="'tlp-dropdown-split-button-main'"
                    v-else
                    data-test="item-action-details-button"
                />
                <drop-down-button v-bind:is-in-large-mode="false">
                    <drop-down-displayed-embedded />
                </drop-down-button>
            </div>
        </div>
    </div>
</template>

<script>
import CreateNewItemVersionButton from "../ActionsButton/NewItemVersionButton.vue";
import DetailsItemButton from "../ActionsButton/DetailsItemButton.vue";
import DropDownButton from "../DropDown/DropDownButton.vue";
import DropDownDisplayedEmbedded from "../DropDown/DropDownDisplayedEmbedded.vue";
export default {
    name: "ActionsHeader",
    components: {
        DropDownDisplayedEmbedded,
        DropDownButton,
        DetailsItemButton,
        CreateNewItemVersionButton,
    },
    props: {
        item: Object,
    },
};
</script>
