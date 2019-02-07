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
        <div class="tlp-pane-header">
            <h1 class="tlp-pane-title">
                <template v-if="baseline.artifact_title">{{ baseline.artifact_title }}</template>
                <template v-else>No title available</template>
            </h1>
        </div>
        <section class="tlp-pane-section">
            <p>
                Timestamp : {{ baseline.last_modification_date_before_baseline_date }}
            </p>
            <p v-if="baseline.artifact_description">
                Description : {{ baseline.artifact_description }}
            </p>
            <p v-if="baseline.artifact_status">
                Status : {{ baseline.artifact_status }}
            </p>
        </section>
    </div>
</template>


<script>
export default {
    name: "SimplifiedBaseline",

    props: {
        baseline: { mandatory: true, type: Object }
    }
};
</script>
