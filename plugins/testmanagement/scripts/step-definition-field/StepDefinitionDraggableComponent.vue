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
  -->

<template>
    <div class="ttm-definition-step draggable-step-definition">
        <i class="step-definition-drag-icon"></i>
        <div class="ttm-definition-step-rank ttm-execution-step-rank-edition">
            {{ dynamic_rank }}
        </div>
        <div
            v-dompurify-html="step.raw_description"
            v-bind:class="{
                'ttm-definition-step-description-text': is_text(step.description_format),
            }"
            class="draggable-step-description-text"
        ></div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
export default {
    name: "StepDefinitionDraggableComponent",
    props: {
        step: Object,
        dynamic_rank: Number,
    },
    computed: {
        ...mapGetters(["is_text"]),
    },
};
</script>
