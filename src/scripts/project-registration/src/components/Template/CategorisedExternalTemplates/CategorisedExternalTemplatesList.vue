<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
    <div class="project-registration-templates">
        <section
            class="project-registration-default-templates-section"
            data-test="tuleap-templates-section"
        >
            <template-card
                v-for="template of props.templates"
                v-bind:key="template.id"
                v-bind:template="template"
            />
        </section>
    </div>
</template>

<script setup lang="ts">
import type { ExternalTemplateData } from "../../../type";
import TemplateCard from "../TemplateCard.vue";

const props = defineProps<{ templates: ExternalTemplateData[] }>();
</script>
