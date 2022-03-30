<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <div>
        <div
            v-if="is_loading_failed"
            class="tlp-alert-danger tlp-framed-vertically"
            data-test-type="error-message"
        >
            <translate>Cannot fetch baseline</translate>
        </div>

        <content-layout v-else-if="is_loading">
            <baseline-label-skeleton slot="header" />
            <baseline-content-filters-skeleton slot="filters" />

            <content-body-skeleton />
        </content-layout>

        <content-layout v-else>
            <baseline-label slot="header" v-bind:baseline="baseline" />
            <baseline-content-filters slot="filters" />

            <content-body />
        </content-layout>
    </div>
</template>

<script>
import { sprintf } from "sprintf-js";
import BaselineLabelSkeleton from "../common/BaselineLabelSkeleton.vue";
import ContentBodySkeleton from "./ContentBodySkeleton.vue";
import ContentBody from "./ContentBody.vue";
import BaselineLabel from "../common/BaselineLabel.vue";
import { mapGetters } from "vuex";
import BaselineContentFilters from "./BaselineContentFilters.vue";
import BaselineContentFiltersSkeleton from "./BaselineContentFiltersSkeleton.vue";
import ContentLayout from "../common/ContentLayout.vue";

export default {
    name: "ContentPage",
    components: {
        ContentBodySkeleton,
        BaselineLabelSkeleton,
        ContentLayout,
        BaselineContentFilters,
        BaselineContentFiltersSkeleton,
        BaselineLabel,
        ContentBody,
    },

    props: {
        baseline_id: { required: true, type: Number },
    },

    data() {
        return {
            is_loading: true,
            is_loading_failed: false,
        };
    },

    computed: {
        ...mapGetters(["findBaselineById"]),
        baseline() {
            return this.findBaselineById(this.baseline_id);
        },
    },

    created() {
        const title = sprintf(this.$gettext("Baseline #%u"), this.baseline_id);
        this.$emit("title", title);
    },

    mounted() {
        this.loadBaseline();
    },

    methods: {
        async loadBaseline() {
            this.is_loading = true;
            this.is_loading_failed = false;
            try {
                await this.$store.dispatch("current_baseline/load", this.baseline_id);
            } catch (e) {
                this.is_loading_failed = true;
            } finally {
                this.is_loading = false;
            }
        },
    },
};
</script>
