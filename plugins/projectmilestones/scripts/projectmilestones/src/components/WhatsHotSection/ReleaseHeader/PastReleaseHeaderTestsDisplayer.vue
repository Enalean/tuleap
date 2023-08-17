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
  -->

<template>
    <div v-if="is_testplan_activated" class="past-release closed-release-header-badge">
        <i class="release-remaining-icon fa fa-check"></i>
        <span class="release-remaining-value" data-test="number-tests">
            {{ number_tests }}
        </span>
        <span class="release-remaining-text">{{ test_label }}</span>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { MilestoneData } from "../../../type";
import { is_testplan_activated } from "../../../helpers/test-management-helper";

@Component
export default class PastReleaseHeaderTestsDisplayer extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;

    get number_tests(): number {
        if (!this.release_data.campaign) {
            return 0;
        }

        return (
            this.release_data.campaign.nb_of_failed +
            this.release_data.campaign.nb_of_blocked +
            this.release_data.campaign.nb_of_notrun +
            this.release_data.campaign.nb_of_passed
        );
    }

    get is_testplan_activated(): boolean {
        return is_testplan_activated(this.release_data);
    }

    get test_label(): string {
        return this.$ngettext("test", "tests", this.number_tests);
    }
}
</script>
