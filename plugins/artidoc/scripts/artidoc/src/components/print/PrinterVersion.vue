<!--
- Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
-
-->

<template>
    <div id="artidoc-print-version">
        <table style="width: 100%; border-collapse: collapse; border: 0">
            <thead style="display: table-header-group; border: 0">
                <tr style="border: 0">
                    <td style="padding: 0; border: 0">
                        <div class="document-header-space">&nbsp;</div>
                    </td>
                </tr>
            </thead>
            <tbody>
                <tr style="border: 0">
                    <td style="padding: 0; border: 0">
                        <div
                            id="document-title-page"
                            class="document-page"
                            v-if="has_title_page_content"
                        ></div>
                        <div class="document-page">
                            <h1>Table of contents</h1>
                            <div
                                v-for="section in saved_sections"
                                v-bind:key="'toc-' + section.value.id"
                            >
                                <span class="artidoc-display-level">{{
                                    section.value.display_level
                                }}</span>
                                <a
                                    v-if="are_internal_links_allowed"
                                    v-bind:href="`#pdf-section-${section.value.id}`"
                                >
                                    {{ section.value.title }}
                                </a>
                                <template v-else>
                                    {{ section.value.title }}
                                </template>
                            </div>
                        </div>
                        <div class="document-page">
                            <section class="document-content">
                                <img
                                    src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAuIwAAAABAQMAAADTaEu9AAAAA1BMVEX///+nxBvIAAAAG0lEQVQYGe3BAQEAAACCoP6vdkjAAAAAAADgQxcTAAGjL+moAAAAAElFTkSuQmCC"
                                    style="visibility: hidden"
                                />
                                <div
                                    v-for="section in saved_sections"
                                    v-bind:key="section.value.id"
                                    class="document-section"
                                    v-bind:id="`pdf-section-${section.value.id}`"
                                >
                                    <section-printer-version v-bind:section="section" />
                                </div>
                            </section>
                        </div>
                    </td>
                </tr>
            </tbody>
            <tfoot style="display: table-footer-group; border: 0">
                <tr style="border: 0">
                    <td style="padding: 0; border: 0">
                        <div class="document-footer-space">&nbsp;</div>
                    </td>
                </tr>
            </tfoot>
        </table>
        <div id="document-header" class="document-header"></div>
        <div id="document-footer" class="document-footer"></div>
    </div>
</template>

<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import { SECTIONS_COLLECTION } from "@/sections/states/sections-collection-injection-key";
import SectionPrinterVersion from "@/components/print/SectionPrinterVersion.vue";
import { PDF_TEMPLATES_COLLECTION } from "@/pdf/pdf-templates-collection";
import { computed } from "vue";

const { saved_sections } = strictInject(SECTIONS_COLLECTION);
const pdf_templates = strictInject(PDF_TEMPLATES_COLLECTION);

const has_title_page_content = computed(
    () => pdf_templates.selected_template.value?.title_page_content !== "",
);

const is_firefox = navigator.userAgent.toLowerCase().includes("firefox");
const are_internal_links_allowed = !is_firefox;
</script>

<style lang="scss" scoped>
#artidoc-print-version {
    display: none;
}
</style>
