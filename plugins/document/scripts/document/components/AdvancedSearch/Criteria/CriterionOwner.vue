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
    <div class="tlp-form-element document-search-criterion document-search-criterion-owner">
        <div class="document-search-criterion-with-popover">
            <label class="tlp-label" v-bind:for="id">{{ criterion.label }}</label>
            <div class="popover-information">
                <span ref="popover_icon">
                    <i class="fas fa-question-circle popover-search-icon"></i>
                </span>

                <section class="tlp-popover" ref="popover_content">
                    <div class="tlp-popover-arrow"></div>
                    <div class="tlp-popover-header">
                        <h1 class="tlp-popover-title" v-translate>Search help</h1>
                    </div>
                    <div class="tlp-popover-body">
                        <p>
                            <translate>You can search documents owned by a user.</translate>
                            <translate>Accepted input is the user id or its username.</translate>
                        </p>
                    </div>
                </section>
            </div>
        </div>

        <input
            type="text"
            class="tlp-input"
            v-bind:id="id"
            v-bind:value="value"
            v-on:input="$emit('input', $event.target.value)"
            v-bind:data-test="id"
        />
    </div>
</template>

<script setup lang="ts">
import type { Popover } from "tlp";
import { createPopover } from "tlp";
import type { SearchCriterionOwner } from "../../../type";
import { computed, onBeforeUnmount, onMounted, ref } from "@vue/composition-api";

const props = defineProps<{ criterion: SearchCriterionOwner; value: string }>();

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

const id = computed((): string => {
    return "document-criterion-owner-" + props.criterion.name;
});
</script>

<script lang="ts">
import { defineComponent } from "@vue/composition-api";
export default defineComponent({});
</script>
