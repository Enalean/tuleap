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
    <section>
        <div v-if="has_rest_error" class="tlp-alert-danger" data-test="show-error-message">
            {{ error }}
        </div>
        <div v-else-if="is_loading" class="release-loader" data-test="is-loading"></div>
        <div v-else>
            <div class="project-release-widget-content" data-test="widget-content">
                <roadmap-section/>
                <whats-hot-section/>
            </div>
        </div>
    </section>
</template>

<script lang="ts">
import WhatsHotSection from "./WhatsHotSection/WhatsHotSection.vue";
import RoadmapSection from "./RoadmapSection/RoadmapSection.vue";
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Action, Getter, Mutation, State } from "vuex-class";

@Component({
    components: { WhatsHotSection, RoadmapSection }
})
export default class App extends Vue {
    @Prop()
    readonly projectId!: number;
    @State
    readonly is_loading!: boolean;
    @State
    readonly error_message!: string;
    @Getter
    has_rest_error!: boolean;
    @Mutation
    setProjectId!: (projectId: number) => void;
    @Action
    getMilestones!: () => void;

    created(): void {
        this.setProjectId(this.projectId);
        this.getMilestones();
    }

    get error(): string {
        return this.error_message === ""
            ? this.$gettext("Oops, an error occurred!")
            : this.error_message;
    }
}
</script>
