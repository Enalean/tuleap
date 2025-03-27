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
  -
  -->

<template>
    <div
        class="taskboard-cell taskboard-cell-swimlane-header"
        v-bind:class="taskboard_cell_swimlane_header_classes"
        v-if="backlog_items_have_children"
        data-navigation="cell"
    >
        <slot name="toggle">
            <button
                class="taskboard-swimlane-toggle"
                v-bind:class="additional_classnames"
                type="button"
                v-bind:title="title"
                v-on:click="collapseSwimlane(props.swimlane)"
            >
                <i class="fa fa-minus-square" aria-hidden="true"></i>
            </button>
        </slot>
        <slot />
    </div>
</template>
<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";
import { useNamespacedActions, useState, useStore } from "vuex-composition-helpers";
import type { Swimlane } from "../../../../../type";
import type { State } from "../../../../../store/type";

const { $gettext, interpolate } = useGettext();

const props = defineProps<{ swimlane: Swimlane }>();

const { collapseSwimlane } = useNamespacedActions("swimlane", ["collapseSwimlane"]);

const store = useStore();
const taskboard_cell_swimlane_header_classes = computed(
    (): string[] => store.getters["swimlane/taskboard_cell_swimlane_header_classes"],
);

const { backlog_items_have_children } = useState<State>(["backlog_items_have_children"]);

const additional_classnames = computed((): string => `tlp-swatch-${props.swimlane.card.color}`);

const title = computed((): string =>
    interpolate($gettext(`Collapse "%{ label }" swimlane`), {
        label: props.swimlane.card.label,
    }),
);
</script>
