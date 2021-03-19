<!---
  - Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
    <div
        class="element-card"
        v-bind:class="additional_classnames"
        draggable="true"
        v-bind:data-element-id="element.artifact_id"
    >
        <div class="element-card-content">
            <div class="element-card-xref-label">
                <a
                    v-bind:href="`/plugins/tracker/?aid=${element.artifact_id}`"
                    class="element-card-xref"
                    v-bind:class="`element-card-xref-${element.tracker.color_name}`"
                    data-not-drag-handle="true"
                >
                    {{ element.artifact_xref }}
                </a>
                <span class="element-card-label">{{ element.artifact_title }}</span>
            </div>
        </div>
        <div class="element-card-accessibility" v-if="show_accessibility_pattern"></div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { ProgramElement } from "../../../type";
import { namespace } from "vuex-class";

const configuration = namespace("configuration");

@Component({})
export default class ToBePlannedCard extends Vue {
    @Prop({ required: true })
    readonly element!: ProgramElement;

    @configuration.State
    readonly accessibility!: boolean;

    @configuration.State
    readonly can_create_program_increment!: boolean;

    get show_accessibility_pattern(): boolean {
        return this.accessibility && this.element.background_color !== "";
    }

    get additional_classnames(): string {
        const classnames = [`element-card-${this.element.tracker.color_name}`];

        if (this.can_create_program_increment) {
            classnames.push("element-draggable-item");
        }
        if (this.element.background_color) {
            classnames.push(`element-card-background-${this.element.background_color}`);
        }

        if (this.show_accessibility_pattern) {
            classnames.push("element-card-with-accessibility");
        }

        return classnames.join(" ");
    }
}
</script>
