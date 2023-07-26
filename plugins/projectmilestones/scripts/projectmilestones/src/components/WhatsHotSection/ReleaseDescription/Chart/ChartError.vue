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
        <div v-if="has_error_duration" class="tlp-alert-warning" data-test="error-duration">
            {{ message_error_duration }}
        </div>
        <div v-if="has_error_start_date" class="tlp-alert-warning" data-test="error-start-date">
            {{ message_error_start_date }}
        </div>
        <div
            v-if="!has_error_duration && !has_error_start_date && is_under_calculation"
            class="tlp-alert-warning"
            data-test="error-calculation"
        >
            {{ message_error_under_calculation }}
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";

@Component
export default class ChartError extends Vue {
    @Prop({ required: true })
    has_error_duration!: boolean;

    @Prop({ required: true })
    message_error_duration!: string;

    @Prop({ required: true })
    has_error_start_date!: boolean;

    @Prop({ required: true })
    message_error_start_date!: string;

    @Prop({ required: true })
    is_under_calculation!: boolean;

    @Prop({ required: true })
    message_error_under_calculation!: string;
}
</script>
