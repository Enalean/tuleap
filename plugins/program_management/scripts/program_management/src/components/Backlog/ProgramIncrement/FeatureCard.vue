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
            data-test="card-tooltip"
        >
            <div class="element-card" v-bind:class="additional_classnames" data-test="feature-card">
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
<script setup lang="ts">
import { computed } from "vue";
import { useNamespacedState } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import FeatureCardBacklogItems from "./FeatureCardBacklogItems.vue";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import type { Feature } from "../../../type";
import {
    getAccessibilityClasses,
    showAccessibilityPattern,
} from "../../../helpers/element-card-css-extractor";
import type { ConfigurationState } from "../../../store/configuration";

const { $gettext } = useGettext();

const { accessibility, can_create_program_increment, has_plan_permissions } =
    useNamespacedState<ConfigurationState>("configuration", [
        "accessibility",
        "can_create_program_increment",
        "has_plan_permissions",
    ]);

const props = defineProps<{
    feature: Feature;
    program_increment: ProgramIncrement;
}>();

const show_accessibility_pattern = computed((): boolean =>
    showAccessibilityPattern(props.feature, accessibility.value),
);

const is_draggable = computed(
    (): boolean => props.program_increment.user_can_plan && has_plan_permissions.value,
);

const additional_classnames = computed((): string => {
    const classnames = getAccessibilityClasses(props.feature, accessibility.value);

    if (!props.feature.is_open) {
        classnames.push("element-card-closed");
    }

    if (can_create_program_increment.value && is_draggable.value) {
        classnames.push("element-draggable-item");
    }

    return classnames.join(" ");
});

const additional_tooltip_classnames = computed((): string => {
    const classnames = ["element-card-container"];

    if (!is_draggable.value) {
        classnames.push("tlp-tooltip");
        classnames.push("tlp-tooltip-left");
    }

    return classnames.join(" ");
});

const reason_why_feature_is_not_draggable = computed((): string => {
    if (is_draggable.value) {
        return "";
    }

    if (!has_plan_permissions.value) {
        return $gettext("You cannot plan items");
    }

    return $gettext(
        "The feature is not plannable, user does not have permission to update artifact or field link.",
    );
});
</script>
