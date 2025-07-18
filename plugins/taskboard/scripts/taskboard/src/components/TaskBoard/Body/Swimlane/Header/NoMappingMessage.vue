<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <p class="tlp-text-warning taskboard-no-mapping">
        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
        <span v-dompurify-html="message"></span>
        {{ $gettext("Please edit the card to change the status, or add children if possible.") }}
    </p>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { Card } from "../../../../../type";
import { useGettext } from "vue3-gettext";

const gettext_provider = useGettext();

const props = defineProps<{
    card: Card;
}>();

const message = computed((): string => {
    if (!props.card.mapped_list_value) {
        return gettext_provider.$gettext("This card does not have any status.");
    }

    return gettext_provider.interpolate(
        gettext_provider.$gettext(
            "This card has status <strong>%{ label }</strong> that does not map to current taskboard columns.",
        ),
        { label: props.card.mapped_list_value.label },
    );
});
</script>
