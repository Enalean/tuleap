<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <div class="ttm-definition-step">
        <div class="ttm-definition-step-rank">{{ step.rank }}</div>
        <div class="ttm-definition-step-description">
            <input
                type="hidden"
                v-bind:name="'artifact[' + fieldId + '][id][]'"
                v-bind:value="step.id">
            <textarea
                ref="description"
                v-bind:id="'field_new_description_' + step.rank + '_' + fieldId"
                v-bind:name="'artifact[' + fieldId + '][description][]'"
                rows="10"
                cols="50"
            >{{ step.raw_description }}</textarea>
        </div>
    </div>
</template>

<script>
    import { textarea } from 'tuleap';

    export default {
        name: "StepDefinitionEntry",
        props: {
            step: Object,
            fieldId: Number
        },
        mounted() {
            this.loadRTE();
        },
        methods: {
            loadRTE() {
                const element = this.$refs.description;

                new textarea.RTE(
                    element,
                    {
                        toggle: true,
                        default_in_html: false,
                        id: element.id,
                        name: 'artifact[' + this.fieldId + '][description_format][]',
                        htmlFormat: this.step.description_format !== 'text'
                    }
                );
            }
        }
    }
</script>