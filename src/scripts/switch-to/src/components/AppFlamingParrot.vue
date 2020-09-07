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
    <div class="modal hide fade" id="switch-to-modal" role="dialog" v-bind:aria-label="aria_label">
        <switch-to-header class="modal-header" v-bind:modal="null" />
        <switch-to-body class="modal-body" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import $ from "jquery";
import { Component } from "vue-property-decorator";
import SwitchToHeader from "./Header/SwitchToHeader.vue";
import SwitchToBody from "./Body/SwitchToBody.vue";
import { Action, Mutation } from "vuex-class";

@Component({
    components: { SwitchToHeader, SwitchToBody },
})
export default class AppFlamingParrot extends Vue {
    @Action
    private readonly loadHistory!: () => void;

    @Mutation
    private readonly updateFilterValue!: (value: string) => void;

    mounted(): void {
        const modal = this.$el;
        if (!(modal instanceof HTMLElement)) {
            return;
        }

        $(modal)
            // Force autofocus for bootstrap modal
            .on("shown", () => {
                this.loadHistory();
                const input = modal.querySelector("input");
                if (input) {
                    input.focus();
                }
            })
            // Clear filter for bootstrap modal
            .on("hidden", () => {
                this.updateFilterValue("");
            });
    }
    get aria_label(): string {
        return this.$gettext("Switch to…");
    }
}
</script>
