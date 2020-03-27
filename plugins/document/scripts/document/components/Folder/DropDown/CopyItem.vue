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
        v-on:click="copyItem(item)"
        v-bind:class="{ 'tlp-dropdown-menu-item-disabled': pasting_in_progress }"
        v-bind:disabled="pasting_in_progress"
    >
        <i class="fa fa-fw fa-copy tlp-dropdown-menu-item-icon"></i>
        <translate>Copy</translate>
    </a>
</template>
<script>
import { mapState } from "vuex";
import EventBus from "../../../helpers/event-bus.js";

export default {
    name: "CopyItem",
    props: {
        item: Object,
    },
    computed: {
        ...mapState("clipboard", ["pasting_in_progress"]),
    },
    methods: {
        copyItem() {
            if (!this.pasting_in_progress) {
                EventBus.$emit("hide-action-menu");
            }
            this.$store.commit("clipboard/copyItem", this.item);
        },
    },
};
</script>
