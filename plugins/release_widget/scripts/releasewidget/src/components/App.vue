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
        <div v-else><roadmap-section/></div>
    </section>
</template>

<script>
import { mapState, mapGetters } from "vuex";
import RoadmapSection from "./RoadmapSection/RoadmapSection.vue";

export default {
    name: "App",
    components: { RoadmapSection },
    props: {
        projectId: Number
    },
    computed: {
        ...mapState(["error_message", "is_loading"]),
        ...mapGetters(["has_rest_error"]),
        error() {
            return this.error_message === ""
                ? this.$gettext("Oops, an error occurred!")
                : this.error_message;
        }
    },
    created() {
        this.$store.commit("setProjectId", this.projectId);
        this.$store.dispatch("getTotalsBacklogAndUpcomingReleases");
    }
};
</script>
