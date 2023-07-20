<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div class="tlp-alert-danger cross-tracker-report-error" v-if="has_error_message">
        {{ error_message }}
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import Component from "vue-class-component";
import { State, Getter } from "vuex-class";

@Component
export default class ErrorMessage extends Vue {
    @State
    readonly error_message!: string;

    @Getter
    readonly has_error_message!: boolean;
}
</script>
