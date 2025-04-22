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
        class="taskboard-swimlane taskboard-swimlane-collapsed"
        data-navigation="swimlane"
        tabindex="0"
    >
        <swimlane-header v-bind:swimlane="swimlane">
            <template v-slot:toggle>
                <button
                    class="taskboard-swimlane-toggle"
                    v-bind:class="additional_classnames"
                    type="button"
                    v-bind:title="title"
                    v-on:click="expandSwimlane(swimlane)"
                    data-test="swimlane-toggle"
                >
                    <i class="fa fa-plus-square" aria-hidden="true"></i>
                </button>
            </template>
            <template v-slot:default>
                <div
                    class="taskboard-card taskboard-card-collapsed"
                    tabindex="0"
                    data-navigation="card"
                    data-shortcut="parent-card"
                    v-bind:class="additional_card_classnames"
                >
                    <div class="taskboard-card-content">
                        <card-xref-label
                            v-bind:card="swimlane.card"
                            v-bind:label="swimlane.card.label"
                            class="taskboard-card-xref-label-collapsed"
                        />
                    </div>
                </div>
            </template>
        </swimlane-header>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useNamespacedActions } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import type { Swimlane } from "../../../../type";
import CardXrefLabel from "./Card/CardXrefLabel.vue";
import SwimlaneHeader from "./Header/SwimlaneHeader.vue";

const { $gettext } = useGettext();

const { expandSwimlane } = useNamespacedActions("swimlane", ["expandSwimlane"]);

const props = defineProps<{
    swimlane: Swimlane;
}>();

const additional_classnames = computed((): string => {
    return `tlp-swatch-${props.swimlane.card.color}`;
});

const additional_card_classnames = computed((): string => {
    return `taskboard-card-${props.swimlane.card.color}`;
});

const title = $gettext("Expand");
</script>
