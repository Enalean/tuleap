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
    <div class="test-plan-campaign-progressions">
        <div
            class="test-plan-campaign-progression passed"
            v-bind:class="passed_classname"
            v-if="campaign.nb_of_passed"
            v-bind:aria-label="passed_title"
        >
            <div class="test-plan-campaign-progression-bar"></div>
            <div class="test-plan-campaign-progression-value">{{ campaign.nb_of_passed }}</div>
        </div>
        <div
            class="test-plan-campaign-progression failed"
            v-bind:class="failed_classname"
            v-if="campaign.nb_of_failed"
            v-bind:aria-label="failed_title"
        >
            <div class="test-plan-campaign-progression-bar"></div>
            <div class="test-plan-campaign-progression-value">{{ campaign.nb_of_failed }}</div>
        </div>
        <div
            class="test-plan-campaign-progression blocked"
            v-bind:class="blocked_classname"
            v-if="campaign.nb_of_blocked"
            v-bind:aria-label="blocked_title"
        >
            <div class="test-plan-campaign-progression-bar"></div>
            <div class="test-plan-campaign-progression-value">{{ campaign.nb_of_blocked }}</div>
        </div>
        <div
            class="test-plan-campaign-progression notrun"
            v-bind:class="notrun_classname"
            v-if="should_not_run_progress_be_displayed"
            v-bind:aria-label="notrun_title"
            data-test="progress-not-run"
        >
            <div class="test-plan-campaign-progression-bar"></div>
            <div class="test-plan-campaign-progression-value">{{ campaign.nb_of_notrun }}</div>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Campaign } from "../../type";

@Component
export default class CampaignProgression extends Vue {
    @Prop({ required: true })
    readonly campaign!: Campaign;

    private readonly percentage_classname_prefix = "test-plan-campaign-progression-width-";

    private percentage(nb: number): string {
        if (!nb) {
            return "";
        }

        return this.percentage_classname_prefix + Math.round((nb * 100) / this.nb_tests);
    }

    get nb_tests(): number {
        return (
            this.campaign.nb_of_blocked +
            this.campaign.nb_of_failed +
            this.campaign.nb_of_notrun +
            this.campaign.nb_of_passed
        );
    }

    get should_not_run_progress_be_displayed(): boolean {
        if (this.nb_tests === 0) {
            return true;
        }

        return this.campaign.nb_of_notrun > 0;
    }

    get passed_classname(): string {
        return this.percentage(this.campaign.nb_of_passed);
    }

    get blocked_classname(): string {
        return this.percentage(this.campaign.nb_of_blocked);
    }

    get failed_classname(): string {
        return this.percentage(this.campaign.nb_of_failed);
    }

    get notrun_classname(): string {
        if (this.nb_tests === 0) {
            return this.percentage_classname_prefix + 100;
        }

        return this.percentage(this.campaign.nb_of_notrun);
    }

    get passed_title(): string {
        return this.$gettextInterpolate(
            this.$ngettext("%{ nb } passed", "%{ nb } passed", this.nb_tests),
            {
                nb: this.campaign.nb_of_passed,
            }
        );
    }

    get blocked_title(): string {
        return this.$gettextInterpolate(
            this.$ngettext("%{ nb } blocked", "%{ nb } blocked", this.nb_tests),
            {
                nb: this.campaign.nb_of_blocked,
            }
        );
    }

    get failed_title(): string {
        return this.$gettextInterpolate(
            this.$ngettext("%{ nb } failed", "%{ nb } failed", this.nb_tests),
            {
                nb: this.campaign.nb_of_failed,
            }
        );
    }

    get notrun_title(): string {
        return this.$gettextInterpolate(
            this.$ngettext("%{ nb } not run", "%{ nb } not run", this.nb_tests),
            {
                nb: this.campaign.nb_of_notrun,
            }
        );
    }
}
</script>
