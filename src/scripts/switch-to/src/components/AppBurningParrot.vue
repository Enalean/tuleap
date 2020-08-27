<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
  -
  -->

<template>
    <div class="tlp-modal" role="dialog" v-bind:aria-label="aria_label" id="switch-to-modal">
        <switch-to-header class="tlp-modal-header" />
        <switch-to-body class="tlp-modal-body" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { createModal, Modal } from "tlp";
import SwitchToHeader from "./SwitchToHeader.vue";
import SwitchToBody from "./SwitchToBody.vue";

@Component({
    components: { SwitchToHeader, SwitchToBody },
})
export default class App extends Vue {
    mounted(): void {
        const trigger = document.getElementById("switch-to-button");
        if (!(trigger instanceof HTMLElement)) {
            return;
        }

        const modal: Modal = createModal(this.$el);

        trigger.addEventListener("click", () => {
            modal.toggle();
        });
    }

    get aria_label(): string {
        return this.$gettext("Switch to…");
    }
}
</script>
