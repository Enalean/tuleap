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
    <div>
        <comparison-header-async
            v-bind:comparison="null"
            v-bind:from_baseline_id="from_baseline_id"
            v-bind:to_baseline_id="to_baseline_id"
        />

        <statistics/>

        <div class="tlp-framed-vertically">
            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <section class="tlp-pane-section comparison-content">
                        <comparison-content
                            v-bind:from_baseline_id="from_baseline_id"
                            v-bind:to_baseline_id="to_baseline_id"
                        />
                    </section>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
import { sprintf } from "sprintf-js";
import Statistics from "./Statistics.vue";
import ComparisonContent from "./content/ComparisonContent.vue";
import ComparisonHeaderAsync from "./ComparisonHeaderAsync.vue";

export default {
    name: "TransientComparisonPage",

    components: { ComparisonHeaderAsync, ComparisonContent, Statistics },

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
