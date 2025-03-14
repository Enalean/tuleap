<template>
    <div class="slidev-layout cover">
        <div class="my-auto w-full">
            <slot />
            <DataClassification v-bind:level="$slidev.configs.classification" />
        </div>
    </div>
</template>
<style scoped>
.cover {
    background: url("/img/background_cover.webp");
    background-repeat: no-repeat;
    background-position: center center;
    background-size: cover;
    color: #ffffff;
}
</style>
