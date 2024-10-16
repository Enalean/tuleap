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
    <div class="document-layout">
        <aside>
            <div class="tlp-framed table-of-contents">
                <table-of-contents />
            </div>
        </aside>
        <section class="document-content" data-test="document-content">
            <document-content />
        </section>
    </div>
</template>

<script setup lang="ts">
import TableOfContents from "./toc/TableOfContents.vue";
import DocumentContent from "./DocumentContent.vue";
</script>

<style lang="scss" scoped>
@use "@/themes/includes/zindex";

.document-layout {
    display: grid;
    grid-template-columns: 80% 20%;
    height: inherit;
    border-top: 1px solid var(--tlp-neutral-normal-color);
}

.document-content {
    border-right: 1px solid var(--tlp-neutral-normal-color);
    background-color: var(--tlp-white-color);
}

aside {
    z-index: zindex.$toc;
    order: 1;
    height: 100%;
    background: var(--tlp-fade-background-color);
}

.table-of-contents {
    position: sticky;
    top: var(--header-height);
}

@media (max-width: 1024px) {
    .document-layout {
        grid-template-columns: 1fr;
        grid-template-rows: max-content auto;
        height: inherit;
    }

    .document-content {
        border-right: 0;
    }

    .table-of-contents {
        top: 0;
    }

    aside {
        order: -1;
        height: fit-content;
        border-bottom: 1px solid var(--tlp-neutral-normal-color);
    }
}
</style>
