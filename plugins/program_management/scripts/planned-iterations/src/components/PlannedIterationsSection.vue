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
    <div class="planned-iterations">
        <h2 class="planned-iterations-section-title" data-test="planned-iterations-section-title">
            {{ iterations_section_title }}
        </h2>
        <div class="empty-state-page" data-test="app-tmp-empty-state">
            <svg-planned-iterations-empty-state />
            <p
                class="empty-state-text planned-iterations-empty-state-text"
                data-test="planned-iterations-empty-state-text"
            >
                {{ planned_iterations_empty_state_text }}
            </p>
        </div>
    </div>
</template>

<script lang="ts">
import type { IterationLabels } from "../type";

import Vue from "vue";
import { State } from "vuex-class";
import { Component } from "vue-property-decorator";
import { sprintf } from "sprintf-js";
import SvgPlannedIterationsEmptyState from "./SVGPlannedIterationsEmptyState.vue";

@Component({
    components: {
        SvgPlannedIterationsEmptyState,
    },
})
export default class PlannedIterationsSection extends Vue {
    @State
    readonly iterations_labels!: IterationLabels;

    get iterations_section_title(): string {
        return this.iterations_labels.label.length === 0
            ? this.$gettext("Iterations")
            : this.iterations_labels.label;
    }

    get planned_iterations_empty_state_text(): string {
        if (this.iterations_labels.sub_label.length === 0) {
            return this.$gettext("There is no iteration yet.");
        }

        return sprintf(this.$gettext("There is no %s yet."), this.iterations_labels.sub_label);
    }
}
</script>
