<template functionnal>
    <div>
        <h1 class="baseline-statistics-label">
            {{ value }}
            <span class="baseline-statistics-label-title">
                {{ label }}
            </span>
        </h1>
    </div>
</template>

<script>
export default {
    name: "BaselineStatistic",

    props: {
        value: { required: true, type: Number },
        label: { required: true, type: String }
    }
};
</script>
