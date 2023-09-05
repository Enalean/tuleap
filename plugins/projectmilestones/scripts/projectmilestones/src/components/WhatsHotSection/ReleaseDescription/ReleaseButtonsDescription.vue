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
  -->

<template>
    <div class="release-description-link">
        <a
            v-if="get_overview_link"
            v-bind:href="get_overview_link"
            data-test="overview-link"
            class="release-planning-link release-planning-link-item tlp-tooltip tlp-tooltip-top"
            v-bind:data-tlp-tooltip="$gettext('Overview')"
            v-bind:aria-label="$gettext('Overview')"
        >
            <i class="release-description-link-icon far fa-chart-bar" aria-hidden="true"></i>
        </a>
        <slot></slot>
        <a
            v-if="get_cardwall_link"
            v-bind:href="get_cardwall_link"
            data-test="cardwall-link"
            class="release-planning-link release-planning-link-item tlp-tooltip tlp-tooltip-top"
            v-bind:data-tlp-tooltip="$gettext('Cardwall')"
            v-bind:aria-label="$gettext('Cardwall')"
        >
            <i class="release-description-link-icon fa fa-table" aria-hidden="true"></i>
        </a>
        <a
            v-for="pane in get_additional_panes"
            v-bind:key="pane.identifier"
            v-bind:href="pane.uri"
            v-bind:data-test="`pane-link-${pane.identifier}`"
            class="release-planning-link release-planning-link-item tlp-tooltip tlp-tooltip-top"
            v-bind:data-tlp-tooltip="pane.title"
            v-bind:aria-label="pane.title"
        >
            <i
                class="release-description-link-icon fa"
                v-bind:data-test="`pane-icon-${pane.identifier}`"
                v-bind:class="pane.icon_name"
                aria-hidden="true"
            ></i>
        </a>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { MilestoneData, Pane } from "../../../type";
import { State } from "vuex-class";

@Component
export default class ReleaseButtonsDescription extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;
    @State
    readonly project_id!: number;

    get get_overview_link(): string | null {
        return (
            "/plugins/agiledashboard/?group_id=" +
            encodeURIComponent(this.project_id) +
            "&planning_id=" +
            encodeURIComponent(this.release_data.planning.id) +
            "&action=show&aid=" +
            encodeURIComponent(this.release_data.id) +
            "&pane=details"
        );
    }

    get get_cardwall_link(): string | null {
        if (!this.release_data.resources.cardwall) {
            return null;
        }

        return (
            "/plugins/agiledashboard/?group_id=" +
            encodeURIComponent(this.project_id) +
            "&planning_id=" +
            encodeURIComponent(this.release_data.planning.id) +
            "&action=show&aid=" +
            encodeURIComponent(this.release_data.id) +
            "&pane=cardwall"
        );
    }

    get get_additional_panes(): undefined | Pane[] {
        return this.release_data.resources.additional_panes.filter((pane) =>
            ["taskboard", "testplan"].includes(pane.identifier),
        );
    }
}
</script>
