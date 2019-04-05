<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
    <main class="tlp-framed-vertically">
        <div class="tlp-framed-horizontally">
            <comparison-header
                v-bind:from_baseline_id="from_baseline_id"
                v-bind:to_baseline_id="to_baseline_id"
            />

            <statistics/>

            <comparison-content
                v-bind:from_baseline_id="from_baseline_id"
                v-bind:to_baseline_id="to_baseline_id"
            />
        </div>
    </main>
</template>

<script>
import { sprintf } from "sprintf-js";
import Statistics from "./Statistics.vue";
import ComparisonContent from "./content/ComparisonContent.vue";
import ComparisonHeader from "./ComparisonHeader.vue";

export default {
    name: "ComparisonPage",
    components: { ComparisonHeader, ComparisonContent, Statistics },
    props: {
        from_baseline_id: { required: true, type: Number },
        to_baseline_id: { required: true, type: Number }
    },
    created() {
        const title = sprintf(
            this.$gettext("Baselines comparison #%u/#%u"),
            this.from_baseline_id,
            this.to_baseline_id
        );
        this.$emit("title", title);
    }
};
</script>
