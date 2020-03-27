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
    <a
        v-on:click.prevent="showUpdateModal"
        class="tlp-dropdown-menu-item"
        role="menuitem"
        data-test="document-dropdown-update-properties"
    >
        <i class="fa fa-fw fa-list tlp-dropdown-menu-item-icon"></i>
        <translate>
            Update properties
        </translate>
    </a>
</template>

<script>
import EventBus from "../../../helpers/event-bus.js";
export default {
    name: "UpdateProperties",
    props: {
        item: Object,
    },
    methods: {
        showUpdateModal() {
            EventBus.$emit("show-update-item-metadata-modal", {
                detail: { current_item: this.item },
            });
        },
    },
};
</script>
