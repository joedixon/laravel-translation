<template>
    <div class="flex">
        <svg v-show="!isActive && isLoading" v-on:click="setActive" class="cursor-pointer fill-current w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M0 2C0 .9.9 0 2 0h14l4 4v14a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm5 0v6h10V2H5zm6 1h3v4h-3V3z"/></svg>
        <svg v-show="!isActive && hasSaved" v-on:click="setActive" class="cursor-pointer fill-current text-green w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM6.7 9.29L9 11.6l4.3-4.3 1.4 1.42L9 14.4l-3.7-3.7 1.4-1.42z"/></svg>
        <svg v-show="!isActive && hasErrored" v-on:click="setActive" class="cursor-pointer fill-current text-red w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm1.41-1.41A8 8 0 1 0 15.66 4.34 8 8 0 0 0 4.34 15.66zm9.9-8.49L11.41 10l2.83 2.83-1.41 1.41L10 11.41l-2.83 2.83-1.41-1.41L8.59 10 5.76 7.17l1.41-1.41L10 8.59l2.83-2.83 1.41 1.41z"/></svg>
        <svg v-show="!isActive && !hasSaved && !hasErrored && !isLoading" v-on:click="setActive" class="cursor-pointer fill-current w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M12.3 3.7l4 4L4 20H0v-4L12.3 3.7zm1.4-1.4L16 0l4 4-2.3 2.3-4-4z"/></svg>
        <textarea 
            rows="1" 
            v-model="translation" 
            v-bind:class="{ active: isActive }"
            v-on:focus="setActive"
            v-on:blur="storeTranslation"
            ref="trans"
        ></textarea>
    </div>
</template>

<script>
    export default {
        props: ['initialTranslation', 'language', 'group', 'translationKey', 'route'],

        data: function() {
            return {
                isActive: false,
                hasSaved: false,
                hasErrored: false,
                isLoading: false,
                hasChanged: false,
                translation: this.initialTranslation
            }
        },

        methods: {
            setActive: function() {
                this.isActive = true;
                this.$refs.trans.focus();
            },
            storeTranslation: function() {
                this.isActive = false;
                if(!this.hasChanged) {
                    return;
                }
                this.isLoading = true;
                window.axios.post(`/${this.route}/${this.language}`, {
                    language: this.language,
                    group: this.group,
                    key: this.translationKey,
                    value: this.translation
                }).then((response) => {
                    this.hasSaved = true;
                    this.isLoading = false;
                    this.hasChanged = false;
                })
                .catch((error) => {
                    this.hasErrored = true;
                    this.isLoading = false;
                })
            }
        },

        watch: {
            hasSaved: function(hasSaved) {
                if(hasSaved) {
                    setTimeout(() => { this.hasSaved = false }, 3000)
                }
            },
            hasErrored: function(hasErrored) {
                if(hasErrored) {
                    setTimeout(() => { this.hasErrored = false }, 3000)
                }
            },
            translation: function(translation)
            {
                this.hasChanged = true;
            }
        }
    }
</script>
