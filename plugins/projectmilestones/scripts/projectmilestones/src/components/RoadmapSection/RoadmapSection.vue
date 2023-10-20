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
    <div class="project-release-timeframe">
        <span class="project-release-label">{{ $gettext("Roadmap") }}</span>
        <div class="project-other-releases">
            <div class="project-release-time-stripe-icon">
                <i class="fa fa-angle-double-up"></i>
            </div>
            <a class="releases-link" v-bind:href="backlog_link" data-test="backlog-link">
                {{ items_in_backlog_label }}
                {{ upcoming_releases }}
            </a>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { useStore } from "../../stores/root";

@Component
export default class RoadmapSection extends Vue {
    public root_store = useStore();

    @Prop()
    readonly label_tracker_planning!: string;

    get backlog_link(): string {
        return (
            "/plugins/agiledashboard/?action=show-top&group_id=" +
            encodeURIComponent(this.root_store.project_id) +
            "&pane=topplanning-v2"
        );
    }

    get items_in_backlog_label(): string {
        const translated = this.$ngettext(
            "%{nb_backlog_items} item in the backlog.",
            "%{nb_backlog_items} items in the backlog.",
            this.root_store.nb_backlog_items,
        );

        return this.$gettextInterpolate(translated, {
            nb_backlog_items: this.root_store.nb_backlog_items,
        });
    }

    get upcoming_releases(): string {
        const translated = this.$ngettext(
            "%{nb_upcoming_releases} upcoming %{label_tracker}.",
            "%{nb_upcoming_releases} upcoming %{label_tracker}.",
            this.root_store.nb_upcoming_releases,
        );

        return this.$gettextInterpolate(translated, {
            nb_upcoming_releases: this.root_store.nb_upcoming_releases,
            label_tracker: this.label_tracker_planning,
        });
    }
}
</script>
