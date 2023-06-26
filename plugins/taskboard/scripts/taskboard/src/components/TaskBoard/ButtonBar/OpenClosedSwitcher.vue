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
  -
  -->

<template>
    <div class="tlp-button-bar taskboard-open-closed-switcher">
        <div class="tlp-button-bar-item">
            <input
                type="radio"
                id="button-bar-show-closed"
                name="button-bar-open-closed-switcher"
                class="tlp-button-bar-checkbox"
                v-bind:checked="are_closed_items_displayed"
                v-on:change="displayClosedItems"
                data-shortcut="toggle-closed-items"
            />
            <label
                for="button-bar-show-closed"
                class="tlp-button-primary tlp-button-outline tlp-button-small"
                v-bind:title="view_closed_items_title"
                data-test="show-closed-items"
            >
                <i class="fa-solid fa-eye tlp-button-icon" aria-hidden="true"></i>
            </label>
        </div>
        <div class="tlp-button-bar-item">
            <input
                type="radio"
                id="button-bar-hide-closed"
                name="button-bar-open-closed-switcher"
                class="tlp-button-bar-checkbox"
                v-bind:checked="!are_closed_items_displayed"
                v-on:change="hideClosedItems"
                data-shortcut="toggle-closed-items"
            />
            <label
                for="button-bar-hide-closed"
                class="tlp-button-primary tlp-button-outline tlp-button-small"
                v-bind:title="hide_closed_items_title"
                data-test="hide-closed-items"
            >
                <i class="fa-solid fa-eye-slash tlp-button-icon" aria-hidden="true"></i>
            </label>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { Action, State } from "vuex-class";

@Component
export default class OpenClosedSwitcher extends Vue {
    @State
    readonly are_closed_items_displayed!: boolean;

    @Action
    readonly displayClosedItems!: () => Promise<void>;

    @Action
    readonly hideClosedItems!: () => Promise<void>;

    get view_closed_items_title(): string {
        return this.$gettext("View closed items");
    }

    get hide_closed_items_title(): string {
        return this.$gettext("Hide closed items");
    }
}
</script>
