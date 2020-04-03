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
    <div ref="dragula_container">
        <template v-for="(step, index) in steps">
            <div class="ttm-definition-step-draggable" v-bind:key="'add-button-' + step.uuid">
                <step-definition-entry
                    v-bind:key="step.uuid"
                    v-bind:dynamic_rank="index + 1"
                    v-bind:step="step"
                />
                <div v-show="!is_dragging" class="ttm-definition-step-add-bar">
                    <button type="button" class="btn btn-primary" v-on:click="addStep(index + 1)">
                        <i class="fa fa-plus"></i>
                        <translate>Add step</translate>
                    </button>
                </div>
            </div>
        </template>
    </div>
</template>

<script>
import StepDefinitionEntry from "./StepDefinitionEntry.vue";
import { mapState, mapMutations } from "vuex";

export default {
    name: "StepDefinitionDragContainer",
    components: { StepDefinitionEntry },
    computed: {
        ...mapState(["steps", "is_dragging"]),
    },
    watch: {
        is_dragging(new_value) {
            if (new_value === true) {
                window.addEventListener("mousemove", this.replaceDragulaMirror);
            } else {
                window.removeEventListener("mousemove", this.replaceDragulaMirror);
            }
        },
    },
    mounted() {
        this.initContainer(this.$refs.dragula_container);
    },
    methods: {
        ...mapMutations(["addStep", "initContainer"]),
        replaceDragulaMirror(event) {
            const mirrors = document.getElementsByClassName("gu-mirror");
            if (mirrors.length > 0) {
                mirrors[0].style.top = event.pageY + "px";
            }
        },
    },
};
</script>
