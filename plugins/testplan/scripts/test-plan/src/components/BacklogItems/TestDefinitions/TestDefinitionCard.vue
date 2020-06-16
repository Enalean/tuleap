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
    <a
        v-bind:href="'/plugins/tracker/?aid=' + encodeURIComponent(test_definition.id)"
        class="tlp-card tlp-card-selectable test-plan-test-definition-card"
    >
        <div class="test-plan-test-definition-xref-title">
            <span class="test-plan-test-definition-xref">#{{ test_definition.id }}</span>
            <span class="test-plan-test-definition-title">
                {{ test_definition.summary }}
            </span>
        </div>
        <div class="test-plan-test-definition-icons">
            <i
                class="fa fa-cogs test-plan-test-definition-icon-automated-tests"
                aria-hidden="true"
                v-if="test_definition.automated_tests"
                data-test="automated-test-icon"
            ></i>
        </div>
    </a>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { TestDefinition } from "../../../type";

@Component
export default class TestDefinitionCard extends Vue {
    @Prop({ required: true })
    readonly test_definition!: TestDefinition;
}
</script>
