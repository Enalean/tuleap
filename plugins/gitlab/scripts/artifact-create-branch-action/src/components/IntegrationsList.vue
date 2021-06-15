<!--
  - Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
        <div v-for="integration in integrations" v-bind:value="project" v-bind:key="integration.id">
            {{ integration.id }}
            {{ integration.gitlab_repository_url }}
            {{ integration.name }}
        </div>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import type { GitlabIntegration } from "../store/type";
import { State } from "vuex-class";

@Component
export default class IntegrationsList extends Vue {
    @State
    readonly integrations!: Array<GitlabIntegration>;
}
</script>
