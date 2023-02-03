<!--
  - Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
    <div class="popover-information">
        <span ref="popover_icon">
            <i class="fa-solid fa-question-circle popover-search-icon"></i>
        </span>
        <section class="tlp-popover popover-search" ref="popover_content">
            <div class="tlp-popover-arrow"></div>
            <div class="tlp-popover-header">
                <h1 class="tlp-popover-title">{{ $gettext("Search help") }}</h1>
            </div>
            <div class="tlp-popover-body">
                <p>{{ props.description }}</p>
                <p>{{ $gettext("Search allowed pattern:") }}</p>
                <ul>
                    <li>{{ exact_message_pattern }}</li>
                    <li>{{ starting_message_pattern }}</li>
                    <li>{{ finishing_message_pattern }}</li>
                    <li>{{ containing_message_pattern }}</li>
                </ul>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import type { Popover } from "@tuleap/tlp-popovers";
import { createPopover } from "@tuleap/tlp-popovers";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

const props = defineProps<{ description: string }>();

const popover = ref<Popover | undefined>();

const popover_icon = ref<InstanceType<typeof HTMLElement>>();
const popover_content = ref<InstanceType<typeof HTMLElement>>();

onMounted((): void => {
    const trigger = popover_icon.value;
    if (!(trigger instanceof HTMLElement)) {
        return;
    }

    const content = popover_content.value;
    if (!(content instanceof HTMLElement)) {
        return;
    }

    popover.value = createPopover(trigger, content, {
        anchor: trigger,
        placement: "bottom-start",
    });
});

onBeforeUnmount((): void => {
    if (popover.value) {
        popover.value.destroy();
    }
});

const exact_message_pattern = computed((): string => {
    return $gettext('lorem => exactly "lorem"');
});

const starting_message_pattern = computed((): string => {
    return $gettext('lorem* => starting by "lorem"');
});

const finishing_message_pattern = computed((): string => {
    return $gettext('*lorem => finishing by "lorem"');
});

const containing_message_pattern = computed((): string => {
    return $gettext('*lorem* => contains "lorem"');
});
</script>
