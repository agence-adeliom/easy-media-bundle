<template>
    <div :class="{'active': show}"
         class="gesture-overlay"/>
</template>

<style scoped lang="scss">
    .gesture-overlay {
      @apply bg-blue-500 bg-opacity-25 absolute inset-0 invisible z-10 transition-all
    }

    .active {
      @apply visible opacity-100;
    }
</style>

<script>
export default {
    data() {
        return {
            show: false
        }
    },
    created() {
        EventHub.listen('toggle-overlay', () => {
            this.show = true

            setTimeout(() => {
                this.show = false
            }, 200)
        })
    }
}
</script>
