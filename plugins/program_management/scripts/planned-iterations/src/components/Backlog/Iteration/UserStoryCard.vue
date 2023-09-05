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
        <div class="element-card" v-bind:class="additional_classnames" data-test="user-story-card">
            <div class="element-card-content">
                <div class="element-card-metadata">
                    <div class="element-card-xref-label">
                        <a
                            v-bind:href="`/plugins/tracker/?aid=${user_story.id}`"
                            class="element-card-xref"
                            v-bind:class="`element-card-xref-${user_story.tracker.color_name}`"
                        >
                            <div class="element-card-xref-project">
                                <span
                                    class="element-card-xref-project-icon"
                                    v-if="user_story.project.icon"
                                >
                                    {{ user_story.project.icon }}
                                </span>
                                {{ user_story.project.label }}
                                <i class="fas fa-long-arrow-alt-right element-card-xref-icon"></i>
                                {{ user_story.xref }}
                            </div>
                        </a>
                    </div>
                    <div
                        class="element-card-feature-title tlp-badge-secondary tlp-badge-outline"
                        v-if="user_story.feature"
                    >
                        <i class="fas fa-level-up-alt"></i>
                        <a v-bind:href="user_story.feature.uri" class="feature-link">
                            {{ user_story.feature.title }}
                        </a>
                    </div>
                </div>
                <span class="element-card-label">{{ user_story.title }}</span>
            </div>
            <div
                class="element-card-accessibility"
                data-test="element-card-accessibility"
                v-if="show_accessibility"
            ></div>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { namespace } from "vuex-class";
import { Component, Prop } from "vue-property-decorator";
import {
    getAccessibilityClasses,
    showAccessibilityPattern,
} from "../../../helpers/accessibility-helper";

import type { UserStory } from "../../../type";

const configuration = namespace("configuration");

@Component
export default class UserStoryCard extends Vue {
    @Prop({ required: true })
    readonly user_story!: UserStory;

    @configuration.State
    readonly is_accessibility_mode_enabled!: boolean;

    get additional_classnames(): string {
        return getAccessibilityClasses(this.user_story, this.is_accessibility_mode_enabled).join(
            " ",
        );
    }

    get show_accessibility(): boolean {
        return showAccessibilityPattern(this.user_story, this.is_accessibility_mode_enabled);
    }
}
</script>
