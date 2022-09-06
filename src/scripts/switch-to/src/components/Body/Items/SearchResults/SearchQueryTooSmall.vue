<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
    <p class="tlp-text-muted">
        {{ message }}
    </p>
</template>

<script setup lang="ts">
import { FULLTEXT_MINIMUM_LENGTH_FOR_QUERY } from "../../../../stores/type";
import { computed } from "vue";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";

const { $gettext, interpolate } = useGettext();

const message = computed((): string => {
    return interpolate($gettext("Please enter more than %{ n } characters."), {
        n: FULLTEXT_MINIMUM_LENGTH_FOR_QUERY,
    });
});
</script>
