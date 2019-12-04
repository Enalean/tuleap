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
    <button type="button"
            class="taskboard-add-in-place-button tlp-button-primary tlp-button-outline tlp-button-small tlp-tooltip tlp-tooltip-top"
            v-bind:data-tlp-tooltip="title"
            v-bind:aria-label="title"
            v-on:click="$emit('click')"
    >
        <i class="fa fa-plus" aria-hidden="true"></i>
    </button>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";

@Component
export default class AddButton extends Vue {
    get title(): string {
        return this.$gettext("Add new card");
    }
}
</script>
