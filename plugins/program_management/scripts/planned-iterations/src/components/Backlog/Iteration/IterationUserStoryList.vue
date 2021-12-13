<!--
  - Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div class="planned-iteration-content-items" data-test="iteration-user-story-list">
        <backlog-element-skeleton v-if="is_loading" data-test="to-be-planned-skeleton" />
        <iteration-no-content v-if="!has_features && !is_loading" data-test="empty-state" />
        <feature-card
            v-else
            v-for="feature in features"
            v-bind:key="feature.id"
            v-bind:feature="feature"
            v-bind:iteration="iteration"
        />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { retrieveIterationContent } from "../../../helpers/iteration-content-retriever";

import IterationNoContent from "./IterationNoContent.vue";
import FeatureCard from "./FeatureCard.vue";
import BacklogElementSkeleton from "../../BacklogElementSkeleton.vue";

import type { Feature, Iteration } from "../../../type";

@Component({
    components: {
        IterationNoContent,
        FeatureCard,
        BacklogElementSkeleton,
    },
})
export default class IterationUserStoryList extends Vue {
    @Prop({ required: true })
    readonly iteration!: Iteration;

    private features: Feature[] = [];
    private is_loading = false;

    async mounted(): Promise<void> {
        this.is_loading = true;
        this.features = await retrieveIterationContent(this.iteration.id);
        this.is_loading = false;
    }

    get has_features(): boolean {
        return this.features.length > 0;
    }
}
</script>
