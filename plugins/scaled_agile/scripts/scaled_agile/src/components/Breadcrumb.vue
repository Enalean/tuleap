<!--
  - Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
    <div class="breadcrumb-container scaled-agile-breadcrumb">
        <nav class="breadcrumb">
            <div class="breadcrumb-item breadcrumb-project">
                <a v-bind:href="projectUrl()" class="breadcrumb-link">
                    {{ project_public_name }}
                </a>
            </div>
            <div class="breadcrumb-item">
                <a v-bind:href="pluginUrl()" class="breadcrumb-link" v-translate>Scaled Agile</a>
            </div>
        </nav>
        <h1 class="scaled-agile-title-header" v-translate>Scaled Agile</h1>
    </div>
</template>

<script lang="ts">
import { Component, Prop } from "vue-property-decorator";
import Vue from "vue";

@Component({})
export default class Breadcrumb extends Vue {
    @Prop({ required: true })
    readonly project_public_name!: string;

    @Prop({ required: true })
    readonly project_short_name!: string;

    public projectUrl(): string {
        return `/projects/${this.project_short_name}`;
    }

    public pluginUrl(): string {
        return `/scaled_agile/${encodeURIComponent(this.project_short_name)}`;
    }
}
</script>
