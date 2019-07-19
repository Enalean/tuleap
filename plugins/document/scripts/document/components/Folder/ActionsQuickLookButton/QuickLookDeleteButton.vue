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
        v-if="item.user_can_write"
        type="button"
        v-bind:class="{
            'tlp-button-small tlp-button-outline tlp-button-danger': ! isInDropdown,
            'tlp-dropdown-menu-item tlp-dropdown-menu-item-danger': isInDropdown
        }"
        v-on:click="processDeletion"
        data-test="quick-look-delete-button"
    >
        <i
            class="fa fa-trash-o"
            v-bind:class="{
                'tlp-button-icon': ! isInDropdown,
                'fa-fw tlp-dropdown-menu-item-icon': isInDropdown
            }"
        ></i>
        <translate>Delete</translate>
    </button>
</template>

<script>
import EventBus from "../../../helpers/event-bus.js";

export default {
    name: "QuickLookDeleteButton",
    props: {
        item: Object,
        isInDropdown: {
            type: Boolean,
            default: false
        }
    },
    methods: {
        processDeletion() {
            EventBus.$emit("show-confirm-item-deletion-modal", {
                detail: { current_item: this.item }
            });
        }
    }
};
</script>
