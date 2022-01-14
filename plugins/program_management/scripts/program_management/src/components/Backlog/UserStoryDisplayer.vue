<!--
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -->

<template>
    <div class="element-card" v-bind:class="additional_classnames">
        <div class="element-card-content">
            <div class="element-card-xref-label">
                <a
                    v-bind:href="`/plugins/tracker/?aid=${user_story.id}`"
                    class="element-card-xref"
                    v-bind:class="`element-card-xref-${user_story.tracker.color_name}`"
                >
                    <span aria-hidden="true">{{ user_story.project.icon }}</span>
                    {{ user_story.project.label }}
                    <i class="fas fa-fw fa-long-arrow-alt-right"></i>
                    {{ user_story.xref }}
                </a>
                <span class="element-card-label">{{ user_story.title }}</span>
            </div>
        </div>
        <div class="element-card-accessibility" v-if="show_accessibility_pattern"></div>
    </div>
</template>

<script lang="ts">
import { namespace } from "vuex-class";
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { UserStory } from "../../helpers/UserStories/user-stories-retriever";
import {
    getAccessibilityClasses,
    showAccessibilityPattern,
} from "../../helpers/element-card-css-extractor";

const configuration = namespace("configuration");

@Component
export default class UserStoryDisplayer extends Vue {
    @Prop({ required: true })
    readonly user_story!: UserStory;

    @configuration.State
    readonly accessibility!: boolean;

    get additional_classnames(): string {
        const classnames = getAccessibilityClasses(this.user_story, this.accessibility);

        if (!this.user_story.is_open) {
            classnames.push("element-card-closed");
        }

        return classnames.join(" ");
    }

    get show_accessibility_pattern(): boolean {
        return showAccessibilityPattern(this.user_story, this.accessibility);
    }
}
</script>
