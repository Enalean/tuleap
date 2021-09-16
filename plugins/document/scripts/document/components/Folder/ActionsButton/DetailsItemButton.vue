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
        v-on:click="goToDetails"
        class="tlp-button-primary"
        v-bind:class="buttonClass"
        data-test="docman-go-to-details"
    >
        <i class="fa fa-list tlp-button-icon"></i>
        <translate>Details</translate>
    </button>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import { namespace } from "vuex-class";
import { redirectToUrl } from "../../../helpers/location-helper";
import type { Item } from "../../../type";

const configuration = namespace("configuration");

@Component
export default class DetailsItemButton extends Vue {
    @Prop({ required: true })
    readonly item!: Item;

    @Prop({ required: true })
    readonly buttonClass!: string;

    @configuration.State
    readonly project_id!: number;

    goToDetails(): void {
        redirectToUrl(
            `/plugins/docman/?group_id=${this.project_id}&id=${this.item.id}&action=details&section=details`
        );
    }
}
</script>
