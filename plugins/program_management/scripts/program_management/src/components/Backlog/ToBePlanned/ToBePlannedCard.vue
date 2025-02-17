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
        class="element-backlog-item"
        v-bind:draggable="has_plan_permissions"
        v-bind:data-element-id="feature.id"
    >
        <div
            class="element-card"
            v-bind:class="additional_classnames"
            data-test="to-be-planned-card"
            v-bind:title="user_has_no_permission_title"
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
<script setup lang="ts">
import { computed } from "vue";
import { useNamespacedState } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import type { Feature } from "../../../type";
import ToBePlannedBacklogItems from "./ToBePlannedBacklogItems.vue";
import {
    getAccessibilityClasses,
    showAccessibilityPattern,
} from "../../../helpers/element-card-css-extractor";
import type { ConfigurationState } from "../../../store/configuration";

const { $gettext } = useGettext();

const { accessibility, has_plan_permissions } = useNamespacedState<ConfigurationState>(
    "configuration",
    ["accessibility", "has_plan_permissions"],
);

const props = defineProps<{ feature: Feature }>();

const show_accessibility_pattern = computed((): boolean =>
    showAccessibilityPattern(props.feature, accessibility.value),
);

const additional_classnames = computed((): string => {
    const classnames = getAccessibilityClasses(props.feature, accessibility.value);

    if (has_plan_permissions.value) {
        classnames.push("element-draggable-item");
    } else {
        classnames.push("element-not-draggable");
    }

    return classnames.join(" ");
});

const user_has_no_permission_title = computed((): string =>
    has_plan_permissions.value ? "" : $gettext("You cannot plan items"),
);
</script>
