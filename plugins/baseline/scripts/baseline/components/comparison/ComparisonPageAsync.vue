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
        <comparison-page-skeleton v-if="is_fetching" />
        <comparison-page v-else v-bind:comparison="comparison" />
    </div>
</template>

<script>
import ComparisonPageSkeleton from "./ComparisonPageSkeleton.vue";
import ComparisonPage from "./ComparisonPage.vue";
import { sprintf } from "sprintf-js";
import { getComparison } from "../../api/rest-querier";

export default {
    name: "ComparisonPageAsync",

    components: { ComparisonPageSkeleton, ComparisonPage },

    props: {
        comparison_id: { required: true, type: Number },
    },

    data() {
        return {
            comparison: null,
            is_fetching: true,
        };
    },

    created() {
        const title = sprintf(this.$gettext("Baselines comparison #%u"), this.comparison_id);
        this.$emit("title", title);
    },

    mounted() {
        this.fetchComparison();
    },

    methods: {
        async fetchComparison() {
            this.is_fetching = true;
            try {
                this.comparison = await getComparison(this.comparison_id);
            } finally {
                this.is_fetching = false;
            }
        },
    },
};
</script>
