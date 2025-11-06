<template>
    <span class="level">{{ level }}</span>
</template>
<script setup lang="ts">
import { ref } from "vue";

const props = defineProps<{
    level?: string;
}>();

const text_color = ref("#FF");

// See https://www.first.org/tlp/ for the color reference
switch (props.level) {
    case "TLP:RED":
        text_color.value = "#FF2B2B";
        break;
    case "TLP:AMBER":
        text_color.value = "#FFC000";
        break;
    case "TLP:GREEN":
        text_color.value = "#33FF00";
        break;
    case "TLP:CLEAR":
        text_color.value = "#FFFFFF";
        break;
    default:
        throw new Error(props.level + " is not a known classification level");
}
</script>
<style scoped>
.level {
    padding-right: 0.3em;
    padding-left: 0.3em;
    background-color: #000000;
    color: v-bind(text_color);
}
</style>
