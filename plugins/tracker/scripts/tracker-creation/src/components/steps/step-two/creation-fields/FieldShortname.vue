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
    <div class="tlp-form-element">
        <label class="tlp-label" for="tracker-shortname">
            <translate>Shortname</translate>
            <i class="fa fa-asterisk"></i>
        </label>
        <input type="text" class="tlp-input" id="tracker-shortname" name="tracker-shortname" />
        <p class="tlp-text-info">
            <i class="fa fa-life-saver"></i>
            <translate>Avoid spaces and ponctuation</translate>
        </p>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";

@Component
export default class StepTwo extends Vue {}
</script>
