<template>
    <div class="flex">
        <svg v-show="!isActive" class="fill-current w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M12.3 3.7l4 4L4 20H0v-4L12.3 3.7zm1.4-1.4L16 0l4 4-2.3 2.3-4-4z"/></svg>
        <textarea 
            rows="1" 
            v-model="translation" 
            v-bind:class="{ active: isActive }"
            v-on:focus="setActive"
            v-on:blur="setInactive"
        ></textarea>
    </div>
</template>

<script>
    export default {
        props: ['translation'],
        data: function() {
            return {
                // translation: '',
                isActive: false 
            }
        },
        methods: {
            setActive: function() {
            this.isActive = true;
            },
            setInactive: function() {
                this.isActive = false;
            }
        }
    }
</script>
