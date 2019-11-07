<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div>
        <h3 v-translate>Tuleap templates</h3>

        <section class="project-registration-default-templates-section">
            <tuleap-template-card v-for="template of tuleap_templates"
                                  v-bind:key="template.template_name"
                                  v-bind:template="template"
            />
        </section>

        <hr>
        <div class="project-registration-button-container">
            <button type="button" class="tlp-button-primary tlp-button-large tlp-form-element-disabled project-registration-next-button" disabled>
                <span v-translate>Next</span> <i class="fa fa-long-arrow-right tlp-button-icon-right"></i>
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import TuleapTemplateCard from "./TuleapTemplateCard.vue";
import { State } from "vuex-class";
import { TemplateData } from "../../type";
@Component({
    components: { TuleapTemplateCard }
})
export default class TuleapTemplateList extends Vue {
    @State
    tuleap_templates!: TemplateData[];
}
</script>
