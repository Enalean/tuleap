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
        <template v-else-if="nb_tests > 0">
            <span class="test-plan-backlog-item-coverage-text" data-test="nb-tests">
                {{ nb_tests_title }}
            </span>
            <span
                class="fa-stack test-plan-backlog-item-coverage-icon"
                v-bind:class="stack_class"
                data-test="backlog-item-icon"
            >
                <i class="fa fa-circle fa-stack-2x" v-if="icon_class"></i>
                <i
                    class="fa fa-fw fa-stack-1x test-plan-backlog-item-coverage-icon-symbol"
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

interface TestStats {
    passed: number;
    failed: number;
    blocked: number;
    notrun: number;
    blank: number;
}

@Component
export default class BacklogItemCoverage extends Vue {
    @Prop({ required: true })
    readonly backlog_item!: BacklogItem;

    get nb_tests(): number {
        return this.backlog_item.test_definitions.length;
    }

    get nb_tests_title(): string {
        return this.$gettextInterpolate(
            this.$ngettext("%{ nb } test", "%{ nb } tests", this.nb_tests),
            { nb: this.nb_tests }
        );
    }

    get stats(): TestStats {
        const stats: TestStats = {
            passed: 0,
            failed: 0,
            blocked: 0,
            notrun: 0,
            blank: 0,
        };

        for (const test_definition of this.backlog_item.test_definitions) {
            const status = test_definition.test_status || "blank";
            stats[status]++;
        }

        return stats;
    }

    get icon_class(): string {
        const stats = this.stats;
        if (stats.failed > 0) {
            return "fa-times-circle";
        }

        if (stats.blocked > 0) {
            return "fa-exclamation-circle";
        }

        if (stats.notrun > 0 || stats.blank > 0) {
            return "fa-question-circle";
        }

        return "fa-check-circle";
    }

    get stack_class(): string {
        const stats = this.stats;
        if (stats.failed > 0) {
            return "test-plan-backlog-item-coverage-icon-failed";
        }

        if (stats.blocked > 0) {
            return "test-plan-backlog-item-coverage-icon-blocked";
        }

        if (stats.notrun > 0 || stats.blank > 0) {
            return "test-plan-backlog-item-coverage-icon-notrun";
        }

        return "test-plan-backlog-item-coverage-icon-passed";
    }
}
</script>
