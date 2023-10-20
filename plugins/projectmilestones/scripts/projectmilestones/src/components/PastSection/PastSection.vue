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
    <div class="project-release-timeframe">
        <span class="project-release-label" v-if="root_store.last_release">{{
            $gettext("Recently closed")
        }}</span>
        <release-displayer
            v-if="root_store.last_release"
            v-bind:key="root_store.last_release.id"
            v-bind:release_data="root_store.last_release"
            v-bind:is-past-release="true"
            v-bind:is-open="false"
        />
        <span class="project-release-label"> {{ $gettext("Past") }} </span>
        <div class="project-other-releases">
            <div class="project-release-time-stripe-icon">
                <i class="fa fa-angle-double-down"></i>
            </div>
            <a class="releases-link" v-bind:href="past_release_link" data-test="past-releases-link">
                {{ past_releases }}
            </a>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import ReleaseDisplayer from "../WhatsHotSection/ReleaseDisplayer.vue";
import { useStore } from "../../stores/root";

@Component({
    components: {
        ReleaseDisplayer,
    },
})
export default class PastSection extends Vue {
    public root_store = useStore();

    @Prop()
    readonly label_tracker_planning!: string;

    get past_release_link(): string {
        return (
            "/plugins/agiledashboard/?action=show-top&group_id=" +
            encodeURIComponent(this.root_store.project_id) +
            "&pane=topplanning-v2&load-all=1"
        );
    }

    get past_releases(): string {
        const translated = this.$ngettext(
            "%{nb_past} past %{label_tracker}",
            "%{nb_past} past %{label_tracker}",
            this.root_store.nb_past_releases,
        );

        return this.$gettextInterpolate(translated, {
            nb_past: this.root_store.nb_past_releases,
            label_tracker: this.label_tracker_planning,
        });
    }
}
</script>
