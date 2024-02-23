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
    <div class="project-registration-templates" v-if="root_store.tuleap_templates.length > 0">
        <section
            class="project-registration-default-templates-section"
            data-test="tuleap-templates-section"
        >
            <template-card
                v-for="template of root_store.tuleap_templates"
                v-bind:key="template.id"
                v-bind:template="template"
            />
        </section>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import TemplateCard from "../TemplateCard.vue";
import { useStore } from "../../../stores/root";

@Component({
    components: { TemplateCard },
})
export default class TuleapTemplateList extends Vue {
    public root_store = useStore();
}
</script>
