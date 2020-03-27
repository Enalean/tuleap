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
    <div class="tlp-button-bar document-view-switcher">
        <div class="tlp-button-bar-item" v-bind:title="large_view_title">
            <input
                type="radio"
                name="view-switcher"
                id="view-switcher-large"
                class="tlp-button-bar-checkbox"
                value="large"
                v-bind:checked="is_embedded_in_large_view"
                v-on:click="switchToLargeView()"
                data-test="view-switcher-large"
            />
            <label
                for="view-switcher-large"
                class="tlp-button-primary tlp-button-outline tlp-button-small"
                v-bind:title="large_view_title"
            >
                <i class="fa fa-tlp-text-large"></i>
            </label>
        </div>
        <div class="tlp-button-bar-item" v-bind:title="narrow_view_title">
            <input
                type="radio"
                name="view-switcher"
                id="view-switcher-narrow"
                class="tlp-button-bar-checkbox"
                value="narrow"
                v-bind:checked="!is_embedded_in_large_view"
                data-test="view-switcher-narrow"
                v-on:click="switchToNarrowView()"
            />
            <label
                for="view-switcher-narrow"
                class="tlp-button-primary tlp-button-outline tlp-button-small"
                v-bind:title="narrow_view_title"
            >
                <i class="fa fa-tlp-text-narrow"></i>
            </label>
        </div>
    </div>
</template>

<script>
import { mapState } from "vuex";
export default {
    computed: {
        ...mapState(["currently_previewed_item", "is_embedded_in_large_view"]),
        narrow_view_title() {
            return this.$gettext("Narrow view");
        },
        large_view_title() {
            return this.$gettext("Large view");
        },
    },
    methods: {
        switchToLargeView() {
            this.$store.dispatch("displayEmbeddedInLargeMode", this.currently_previewed_item);
        },
        switchToNarrowView() {
            this.$store.dispatch("displayEmbeddedInNarrowMode", this.currently_previewed_item);
        },
    },
};
</script>
