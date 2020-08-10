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
    <div class="test-plan-backlog-item-coverage">
        <template v-if="backlog_item.is_loading_test_definitions">
            <span class="tlp-skeleton-text test-plan-backlog-item-coverage-text-skeleton"></span>
            <span class="tlp-skeleton-text test-plan-backlog-item-coverage-icon-skeleton"></span>
        </template>
        <template v-else-if="test_status !== null">
            <span class="test-plan-backlog-item-coverage-text" data-test="nb-tests">
                {{ nb_tests_title }}
            </span>
            <span
                class="test-plan-backlog-item-coverage-icon"
                v-bind:class="stack_class"
                data-test="backlog-item-icon"
            >
                <i
                    class="fa test-plan-backlog-item-coverage-icon-symbol"
                    v-bind:class="icon_class"
                ></i>
            </span>
        </template>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { BacklogItem } from "../../type";
import {
    computeTestStats,
    getTestStatusFromStats,
    TestStats,
} from "../../helpers/BacklogItems/compute-test-stats";

@Component
export default class BacklogItemCoverage extends Vue {
    @Prop({ required: true })
    readonly backlog_item!: BacklogItem;

    get nb_tests(): number {
        return Object.values(this.stats).reduce((a: number, b: number): number => {
            return a + b;
        });
    }

    get nb_tests_title(): string {
        return this.$gettextInterpolate(
            this.$ngettext("%{ nb } planned test", "%{ nb } planned tests", this.nb_tests),
            { nb: this.nb_tests }
        );
    }

    get stats(): Readonly<TestStats> {
        return computeTestStats(this.backlog_item);
    }

    get test_status(): keyof TestStats | null {
        return getTestStatusFromStats(this.stats);
    }

    get icon_class(): string {
        switch (this.test_status) {
            case null:
                return "";
            case "failed":
                return "fa-times-circle";
            case "blocked":
                return "fa-exclamation-circle";
            case "notrun":
                return "fa-question-circle";
            case "passed":
                return "fa-check-circle";
            default:
                return ((val: never): never => val)(this.test_status);
        }
    }

    get stack_class(): string {
        switch (this.test_status) {
            case null:
                return "";
            case "failed":
                return "test-plan-backlog-item-coverage-icon-failed";
            case "blocked":
                return "test-plan-backlog-item-coverage-icon-blocked";
            case "notrun":
                return "test-plan-backlog-item-coverage-icon-notrun";
            case "passed":
                return "test-plan-backlog-item-coverage-icon-passed";
            default:
                return ((val: never): never => val)(this.test_status);
        }
    }
}
</script>
