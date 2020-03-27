<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
        class="tlp-dropdown-menu-item"
        role="menuitem"
        v-on:click="cutItem(item)"
        v-bind:class="{ 'tlp-dropdown-menu-item-disabled': pasting_in_progress }"
        v-bind:disabled="pasting_in_progress"
        v-if="can_cut_item"
    >
        <i class="fa fa-fw fa-cut tlp-dropdown-menu-item-icon"></i>
        <translate>Cut</translate>
    </a>
</template>
<script>
import { mapState } from "vuex";
import EventBus from "../../../helpers/event-bus.js";

export default {
    name: "CutItem",
    props: {
        item: Object,
    },
    computed: {
        ...mapState("clipboard", ["pasting_in_progress"]),
        can_cut_item() {
            return this.item.user_can_write && this.item.parent_id !== 0;
        },
    },
    methods: {
        cutItem() {
            if (!this.pasting_in_progress) {
                EventBus.$emit("hide-action-menu");
            }
            this.$store.commit("clipboard/cutItem", this.item);
        },
    },
};
</script>
