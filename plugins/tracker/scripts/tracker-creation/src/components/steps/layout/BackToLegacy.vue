<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <a v-bind:href="url" class="tracker-creation-navigation-back-to-legacy">
        <i class="fa fa-random tracker-footer-external-link-icon"></i>
        <translate>Switch to legacy UI</translate>
    </a>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { State } from "vuex-class";

@Component
export default class BackToLegacy extends Vue {
    @State
    readonly project_id!: number;

    get url() {
        return `/plugins/tracker/?group_id=${encodeURIComponent(this.project_id)}&func=create`;
    }
}
</script>
