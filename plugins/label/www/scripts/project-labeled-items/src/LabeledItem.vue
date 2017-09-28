<template>
    <a v-bind:href="item.html_url" class="labeled-item">
        <div class="labeled-item-icon" v-html="purified_icon"></div>
        <div class="labeled-item-content">
            {{ item.title }}
        </div>
    </a>
</template>
(<script>
    import { sanitize } from 'dompurify';

    export default {
        name: 'LabeledItem',
        props: [ 'item' ],
        computed: {
            purified_icon: function() {
                return sanitize(this.item.small_icon, {
                    USE_PROFILES: { svg: true }
                });
            }
        }
    }
</script>)
