<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div>
        <release-badges-displayer-if-open-sprints
            v-if="open_sprints_exist"
            v-bind:release_data="release_data"
            v-bind:is-open="isOpen"
        />
        <release-badges-displayer-if-only-closed-sprints
            v-else
            v-bind:release_data="release_data"
        />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { MilestoneData } from "../../../type";
import ReleaseBadgesAllSprints from "./ReleaseBadgesAllSprints.vue";
import ReleaseOthersBadges from "./ReleaseOthersBadges.vue";
import ReleaseBadgesClosedSprints from "./ReleaseBadgesClosedSprints.vue";
import ReleaseBadgesDisplayerIfOpenSprints from "./ReleaseBadgesDisplayerIfOpenSprints.vue";
import ReleaseBadgesDisplayerIfOnlyClosedSprints from "./ReleaseBadgesDisplayerIfOnlyClosedSprints.vue";
import { openSprintsExist } from "../../../helpers/milestones-sprints-helper";
@Component({
    components: {
        ReleaseBadgesDisplayerIfOnlyClosedSprints,
        ReleaseBadgesDisplayerIfOpenSprints,
        ReleaseBadgesClosedSprints,
        ReleaseOthersBadges,
        ReleaseBadgesAllSprints,
    },
})
export default class ReleaseBadgesDisplayer extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;
    @Prop()
    readonly isOpen!: boolean;

    get open_sprints_exist(): boolean {
        return openSprintsExist(this.release_data);
    }
}
</script>
