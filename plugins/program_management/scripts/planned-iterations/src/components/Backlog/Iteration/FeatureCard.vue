<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
    <div class="element-backlog-item">
        <div class="element-card" v-bind:class="additional_classnames" data-test="feature-card">
            <div class="element-card-content">
                <div class="element-card-xref-label">
                    <a
                        v-bind:href="`/plugins/tracker/?aid=${feature.id}`"
                        class="element-card-xref"
                        v-bind:class="`element-card-xref-${feature.tracker.color_name}`"
                    >
                        {{ feature.xref }}
                    </a>
                    <span class="element-card-label">{{ feature.title }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";

import type { Feature } from "../../../type";

@Component
export default class FeatureCard extends Vue {
    @Prop({ required: true })
    readonly feature!: Feature;

    get additional_classnames(): string {
        const classnames = [`element-card-${this.feature.tracker.color_name}`];

        if (this.feature.background_color) {
            classnames.push(`element-card-background-${this.feature.background_color}`);
        }

        return classnames.join(" ");
    }
}
</script>
