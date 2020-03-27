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
        type="button"
        class="tlp-dropdown-menu-item tlp-dropdown-menu-item-danger"
        v-on:click="processDeletion"
        v-if="item.user_can_write && is_deletion_allowed"
        data-test="document-delete-item"
    >
        <i class="fa fa-trash-o fa-fw tlp-dropdown-menu-item-icon"></i>
        <translate>Delete</translate>
    </button>
</template>

<script>
import EventBus from "../../../helpers/event-bus.js";
import { mapState } from "vuex";

export default {
    name: "DeleteItem",
    props: {
        item: Object,
    },
    computed: {
        ...mapState(["is_deletion_allowed"]),
    },
    methods: {
        processDeletion() {
            EventBus.$emit("show-confirm-item-deletion-modal", {
                detail: { current_item: this.item },
            });
        },
    },
};
</script>
