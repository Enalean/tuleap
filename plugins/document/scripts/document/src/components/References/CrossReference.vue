<!--
  - Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
    <section class="tlp-pane tracker-cross-reference-nature nature">
        <div class="tlp-pane-container">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title">
                    <i
                        v-if="reference.icon !== ''"
                        class="tlp-pane-title-icon"
                        v-bind:class="reference.icon"
                        aria-hidden="true"
                    ></i>
                    {{ reference.label }}
                </h1>
            </div>
            <section
                v-for="section in reference.sections"
                v-bind:key="section.label"
                class="tlp-pane-section-for-cards"
            >
                <h2 v-if="section.label !== ''" class="tlp-pane-subtitle">{{ section.label }}</h2>
                <a
                    v-for="cross_reference in section.cross_references"
                    v-bind:key="cross_reference.id"
                    v-bind:href="cross_reference.url"
                    class="tlp-card tlp-card-selectable cross-reference tracker-cross-reference-card"
                >
                    <div class="tracker-cross-reference-card-content">
                        <div class="tracker-cross-reference-card-main">
                            <title-badge
                                v-if="cross_reference.title_badge"
                                v-bind:badge="cross_reference.title_badge"
                            />

                            <span class="tracker-cross-reference-title cross-reference-title">
                                {{ cross_reference.title }}
                            </span>
                        </div>
                    </div>
                </a>
            </section>
        </div>
    </section>
</template>

<script setup lang="ts">
import type { CrossReferenceNature } from "../../api/references-rest-querier";
import TitleBadge from "./TitleBadge.vue";

defineProps<{
    reference: CrossReferenceNature;
}>();
</script>

<style scoped lang="scss">
.tracker-cross-reference-nature {
    font-size: 0.875rem;
    line-height: 1.375rem;
}

.tracker-cross-reference-card-main {
    display: flex;
    align-items: baseline;
}

.tracker-cross-reference-title {
    flex-grow: 1;
}
</style>
