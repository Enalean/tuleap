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
    <div class="element-backlog-items" draggable="true" v-bind:data-element-id="feature.id">
        <div
            class="element-card"
            v-bind:class="additional_classnames"
            data-test="to-be-planned-card"
            ref="to_be_planned_card"
        >
            <div class="element-card-content">
                <div class="element-card-xref-label">
                    <a
                        v-bind:href="`/plugins/tracker/?aid=${feature.id}`"
                        class="element-card-xref"
                        v-bind:class="`element-card-xref-${feature.tracker.color_name}`"
                        data-not-drag-handle="true"
                    >
                        {{ feature.xref }}
                    </a>
                    <span class="element-card-label">{{ feature.title }}</span>
                </div>
            </div>
            <div class="element-card-accessibility" v-if="show_accessibility_pattern"></div>
        </div>
        <to-be-planned-backlog-items
            v-if="feature.has_user_story_linked"
            v-bind:to_be_planned_element="feature"
            data-not-drag-handle="true"
            draggable="true"
        />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop, Ref, Watch } from "vue-property-decorator";
import type { Feature } from "../../../type";
import { namespace, State } from "vuex-class";
import ToBePlannedBacklogItems from "./ToBePlannedBacklogItems.vue";
import {
    getAccessibilityClasses,
    showAccessibilityPattern,
} from "../../../helpers/element-card-css-extractor";
import { onGoingMoveFeature } from "../../../helpers/on-going-move-feature-helper";

const configuration = namespace("configuration");

@Component({
    components: { ToBePlannedBacklogItems },
})
export default class ToBePlannedCard extends Vue {
    @Prop({ required: true })
    readonly feature!: Feature;

    @configuration.State
    readonly accessibility!: boolean;

    @configuration.State
    readonly can_create_program_increment!: boolean;

    @State
    readonly ongoing_move_elements_id!: number[];

    @Ref("to_be_planned_card")
    readonly to_be_planned_card!: Element;

    private is_moving = false;

    @Watch("ongoing_move_elements_id")
    feature_after_moving(ongoing_move_elements_id: number[]): void {
        this.is_moving = onGoingMoveFeature(
            ongoing_move_elements_id,
            this.to_be_planned_card,
            this.feature.id,
            this.is_moving
        );
    }

    mounted(): void {
        this.is_moving = onGoingMoveFeature(
            this.ongoing_move_elements_id,
            this.to_be_planned_card,
            this.feature.id,
            this.is_moving
        );
    }

    get show_accessibility_pattern(): boolean {
        return showAccessibilityPattern(this.feature, this.accessibility);
    }

    get additional_classnames(): string {
        const classnames = getAccessibilityClasses(this.feature, this.accessibility);

        if (this.can_create_program_increment) {
            classnames.push("element-draggable-item");
        }

        return classnames.join(" ");
    }
}
</script>
