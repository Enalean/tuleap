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
    <input type="hidden" v-bind:name="csrf_token.name" v-bind:value="csrf_token.value" />
</template>
<script lang="ts">
import Vue from "vue";
import { State } from "vuex-class";
import { Component } from "vue-property-decorator";
import { CSRFToken } from "../../../../store/type.js";

@Component
export default class FieldCSRFToken extends Vue {
    @State
    readonly csrf_token!: CSRFToken;
}
</script>
