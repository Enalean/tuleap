<!--
  - Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
    <div v-if="is_obsolete" class="tlp-alert-warning">
        {{ $gettext("This item is obsolete since %{ date }.", { date: formatted_full_date }) }}
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import type { Item } from "../../type";
import { USER_LOCALE, USER_TIMEZONE } from "../../configuration-keys";
import { strictInject } from "@tuleap/vue-strict-inject";
import { IntlFormatter } from "@tuleap/date-helper";

const { $gettext } = useGettext();

const props = defineProps<{
    item: Item;
}>();

const user_locale = strictInject(USER_LOCALE);
const user_timezone = strictInject(USER_TIMEZONE);
const formatter = IntlFormatter(user_locale, user_timezone, "date");

const obsolescence_date = computed((): string => {
    const obsolescence_property = props.item.properties.find(
        (property) => property.short_name === "obsolescence_date",
    );
    return obsolescence_property ? String(obsolescence_property.value) : "";
});

const formatted_full_date = computed((): string => {
    return formatter.format(obsolescence_date.value);
});

const is_obsolete = computed((): boolean => {
    if (obsolescence_date.value === "") {
        return false;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const date = new Date(String(obsolescence_date.value));

    return date < today;
});
</script>

<style lang="scss" scoped>
.tlp-alert-warning {
    margin: 0 0 var(--tlp-x-large-spacing);
}
</style>
