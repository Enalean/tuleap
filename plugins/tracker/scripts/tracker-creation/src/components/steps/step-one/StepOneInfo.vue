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
    <div>
        <step-label
            v-bind:step-number="$gettext('1')"
            v-bind:step-label="$gettext('Choose a starting point')"
        />
        <p v-translate>
            The tracker template you will select provides preconfigured fields, permissions,
            workflow...
        </p>
        <p v-translate>
            Don't worry, you will still be able to modify your tracker after it has been created.
        </p>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import StepLabel from "../layout/StepLabel.vue";

@Component({
    components: {
        StepLabel,
    },
})
export default class StepOneInfo extends Vue {}
</script>
