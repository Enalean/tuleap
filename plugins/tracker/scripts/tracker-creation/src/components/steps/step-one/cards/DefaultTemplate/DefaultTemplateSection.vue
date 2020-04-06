<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div class="tracker-creation-starting-point-options">
        <default-template-card
            v-for="tracker in default_templates"
            v-bind:key="tracker.id"
            v-bind:tracker="tracker"
        />
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { Tracker } from "../../../../../store/type";
import DefaultTemplateCard from "./DefaultTemplateCard.vue";
import { State } from "vuex-class";

@Component({
    components: {
        DefaultTemplateCard,
    },
})
export default class DefaultTemplateSection extends Vue {
    @State
    readonly default_templates!: Tracker[];
}
</script>
