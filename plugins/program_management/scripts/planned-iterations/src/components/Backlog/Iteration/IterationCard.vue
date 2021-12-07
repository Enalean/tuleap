<!--
  - Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div class="tlp-pane-container planned-iteration-display">
        <div
            class="tlp-pane-header planned-iteration-header"
            v-on:click="toggleIsOpen"
            data-test="iteration-card-header"
        >
            <span
                class="tlp-pane-title planned-iteration-header-label"
                data-test="iteration-header-label"
            >
                <i
                    class="tlp-pane-title-icon fas fa-fw"
                    v-bind:class="[is_open ? 'fa-caret-down' : 'fa-caret-right']"
                    data-test="planned-iteration-toggle-icon"
                    aria-hidden="true"
                />
                {{ iteration.title }}
            </span>
            <div>
                <span
                    v-if="iteration.start_date !== null"
                    class="planned-iteration-header-dates"
                    data-test="iteration-header-dates"
                >
                    {{ formatDate(iteration.start_date) }}
                    <i class="fas fa-long-arrow-alt-right" aria-hidden="true"></i>
                    {{ formatDate(iteration.end_date) }}
                </span>
                <span
                    v-if="iteration.status.length > 0"
                    class="tlp-badge-outline tlp-badge-primary"
                    data-test="iteration-header-status"
                >
                    {{ iteration.status }}
                </span>
            </div>
        </div>
        <div
            class="planned-iteration-info"
            v-if="is_open && iteration.user_can_update"
            data-test="planned-iteration-info"
        >
            <a
                v-bind:href="edition_url"
                class="planned-iteration-info-link"
                v-bind:title="$gettext('Edit')"
                data-test="planned-iteration-info-edit-link"
            >
                <i
                    class="fas fa-pencil-alt planned-iteration-info-link-icon"
                    aria-hidden="true"
                ></i>
                <span v-translate>Edit</span>
            </a>
        </div>
        <section
            class="tlp-pane-section planned-iteration-content"
            v-if="is_open"
            data-test="planned-iteration-content"
        >
            <iteration-user-story-list v-bind:iteration="iteration" v-if="is_open" />
        </section>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { State } from "vuex-class";
import { Component, Prop } from "vue-property-decorator";
import { formatDateYearMonthDay } from "@tuleap/date-helper";
import { buildIterationEditionUrl } from "../../../helpers/create-new-iteration-link-builder";

import IterationUserStoryList from "./IterationUserStoryList.vue";

import type { Iteration, ProgramIncrement } from "../../../type";

@Component({
    components: { IterationUserStoryList },
})
export default class IterationCard extends Vue {
    @Prop({ required: true })
    readonly iteration!: Iteration;

    @State
    readonly program_increment!: ProgramIncrement;

    @State
    readonly user_locale!: string;

    private is_open = false;

    formatDate(date: string): string {
        return formatDateYearMonthDay(this.user_locale, date);
    }

    toggleIsOpen(): void {
        this.is_open = !this.is_open;
    }

    get edition_url(): string {
        return buildIterationEditionUrl(this.iteration.id, this.program_increment.id);
    }
}
</script>
