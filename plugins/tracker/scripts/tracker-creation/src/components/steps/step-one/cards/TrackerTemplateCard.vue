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
    <div class="tracker-creation-template-card">
        <label class="tlp-card tlp-card-selectable tracker-creation-template-card-label">
            <div class="card-glyph">
                <svg-tracker-template />
            </div>
            <div class="card-content">
                <h4 class="card-title" v-translate>Template</h4>
                <div class="card-description">
                    <span class="card-description-content" v-translate>
                        Choose one tracker template among ones available in your platform
                    </span>
                </div>
            </div>
        </label>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import SvgTrackerTemplate from "../svg/SvgTrackerTemplate.vue";

@Component({
    components: {
        SvgTrackerTemplate
    }
})
export default class TrackerTemplateCard extends Vue {}
</script>
