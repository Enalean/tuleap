<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <content-layout>
        <template v-slot:header>
            <comparison-header-skeleton />
        </template>
        <template v-slot:statistics>
            <comparison-statistics-skeleton />
        </template>
        <template v-slot:filters>
            <comparison-content-filters-skeleton />
        </template>

        <template v-slot:default>
            <comparison-content-skeleton />
        </template>
    </content-layout>
</template>
<script>
import ContentLayout from "../common/ContentLayout.vue";
import ComparisonContentSkeleton from "./content/ComparisonContentSkeleton.vue";
import ComparisonHeaderSkeleton from "./ComparisonHeaderSkeleton.vue";
import ComparisonContentFiltersSkeleton from "./ComparisonContentFiltersSkeleton.vue";
import ComparisonStatisticsSkeleton from "./ComparisonStatisticsSkeleton.vue";

export default {
    name: "ComparisonPageSkeleton",
    components: {
        ContentLayout,
        ComparisonContentSkeleton,
        ComparisonHeaderSkeleton,
        ComparisonContentFiltersSkeleton,
        ComparisonStatisticsSkeleton,
    },
};
</script>
