<template>
    <div v-bind:class="notification_class" class="tlp-framed-vertically">
        {{ notification.text }}
    </div>
</template>
<script>
export default {
    name: "Notification",
    props: {
        notification: {
            required: true,
            type: Object
        }
    },
    computed: {
        notification_class() {
            return `tlp-alert-${this.notification.class}`;
        }
    }
};
</script>
