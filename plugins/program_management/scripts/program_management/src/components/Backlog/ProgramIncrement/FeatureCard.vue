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
        v-bind:draggable="is_draggable"
        v-bind:data-element-id="feature.id"
        class="element-backlog-item"
    >
        <div
            v-bind:data-tlp-tooltip="reason_why_feature_is_not_draggable"
            v-bind:class="additional_tooltip_classnames"
        >
            <div
                class="element-card"
                v-bind:class="additional_classnames"
                data-test="feature-card"
                ref="feature_card"
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
        </div>
        <feature-card-backlog-items
            v-if="feature.has_user_story_linked"
            v-bind:feature="feature"
            v-bind:program_increment="program_increment"
            data-not-drag-handle="true"
            draggable="true"
        />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop, Ref } from "vue-property-decorator";
import { namespace } from "vuex-class";
import FeatureCardBacklogItems from "./FeatureCardBacklogItems.vue";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import type { Feature } from "../../../type";
import {
    getAccessibilityClasses,
    showAccessibilityPattern,
} from "../../../helpers/element-card-css-extractor";

const configuration = namespace("configuration");

@Component({
    components: { FeatureCardBacklogItems },
})
export default class FeatureCard extends Vue {
    @Prop({ required: true })
    readonly feature!: Feature;

    @Prop({ required: true })
    readonly program_increment!: ProgramIncrement;

    @configuration.State
    readonly accessibility!: boolean;

    @configuration.State
    readonly can_create_program_increment!: boolean;

    @configuration.State
    readonly has_plan_permissions!: boolean;

    @Ref("feature_card")
    readonly feature_card!: Element;

    private is_moving = false;

    get show_accessibility_pattern(): boolean {
        return showAccessibilityPattern(this.feature, this.accessibility);
    }

    get additional_classnames(): string {
        const classnames = getAccessibilityClasses(this.feature, this.accessibility);

        if (!this.feature.is_open) {
            classnames.push("element-card-closed");
        }

        if (this.can_create_program_increment && this.is_draggable) {
            classnames.push("element-draggable-item");
        }

        return classnames.join(" ");
    }

    get additional_tooltip_classnames(): string {
        const classnames = ["element-card-container"];

        if (!this.is_draggable) {
            classnames.push("tlp-tooltip");
            classnames.push("tlp-tooltip-left");
        }

        return classnames.join(" ");
    }

    get is_draggable(): boolean {
        return this.program_increment.user_can_plan && this.has_plan_permissions;
    }

    get reason_why_feature_is_not_draggable(): string {
        if (this.is_draggable) {
            return "";
        }

        if (!this.has_plan_permissions) {
            return this.$gettext("You cannot plan items");
        }

        return this.$gettext(
            "The feature is not plannable, user does not have permission to update artifact or field link.",
        );
    }
}
</script>
