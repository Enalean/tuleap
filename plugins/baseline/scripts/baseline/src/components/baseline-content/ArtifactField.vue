<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
        <h3 class="baseline-content-artifact-body-field-label">
            <semantic-field-label v-bind:semantic="semantic" v-bind:tracker_id="tracker_id" />
        </h3>
        <p
            v-if="html_content"
            v-dompurify-html="value"
            class="baseline-content-artifact-body-field-value"
        ></p>
        <p v-else class="baseline-content-artifact-body-field-value">{{ value }}</p>
    </div>
</template>

<script>
import SemanticFieldLabel from "../common/SemanticFieldLabel.vue";
export default {
    name: "ArtifactField",

    components: { SemanticFieldLabel },

    props: {
        semantic: {
            required: true,
            type: String,
        },
        tracker_id: {
            required: true,
            type: Number,
        },
        value: {
            required: true,
            type: String,
        },
        html_content: {
            default: false,
            type: Boolean,
        },
    },
};
</script>
