<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="register-new-project-list">
        <h2><span class="tlp-badge-primary register-new-project-section-badge">1</span><span v-translate>What kind of project is it?</span></h2>

        <p v-translate>
            The project template you will select provides some services. Don’t worry, you can always modify your project workspace after your project has been created.
        </p>

        <div class="tlp-alert-warning" v-translate>
            This space is under construction. You can still use the current registration process in the meantime.
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";

@Component
export default class ProjectList extends Vue {}
</script>
