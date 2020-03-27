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
    <div class="document-quick-look-document-action">
        <details-item-button
            v-bind:item="item"
            v-bind:button-class="'tlp-button-small document-quick-look-action-button-margin'"
            v-if="is_details_button_shown"
        />
        <drop-down-quick-look
            v-bind:item="item"
            v-bind:is-details-button-shown="!item.user_can_write"
        />
        <div class="document-header-spacer"></div>
        <quick-look-delete-button v-bind:item="item" />
    </div>
</template>

<script>
import DropDownQuickLook from "../DropDown/DropDownQuickLook.vue";
import QuickLookDeleteButton from "../ActionsQuickLookButton/QuickLookDeleteButton.vue";
import DetailsItemButton from "../ActionsButton/DetailsItemButton.vue";

export default {
    components: {
        DetailsItemButton,
        DropDownQuickLook,
        QuickLookDeleteButton,
    },
    props: {
        item: Object,
    },
    computed: {
        is_details_button_shown() {
            return !this.item.user_can_write;
        },
    },
};
</script>
