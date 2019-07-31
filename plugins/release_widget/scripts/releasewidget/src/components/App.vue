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
import { mapGetters, mapState } from "vuex";
import RoadmapSection from "./RoadmapSection/RoadmapSection.vue";
import WhatsHotSection from "./WhatsHotSection/WhatsHotSection.vue";
import Vue from "vue";

export default Vue.extend({
    name: "App",
    components: { WhatsHotSection, RoadmapSection },
    props: {
        projectId: Number
    },
    computed: {
        ...mapState(["is_loading"]),
        ...mapGetters(["has_rest_error"]),
        error(): string {
            return this.$store.state.error_message === ""
                ? this.$gettext("Oops, an error occurred!")
                : this.$store.state.error_message;
        }
    },
    created(): void {
        this.$store.commit("setProjectId", this.projectId);
        this.$store.dispatch("getMilestones");
    }
});
</script>
