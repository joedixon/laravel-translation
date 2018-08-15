<template>
    <div class="search" v-bind:class="{ 'has-results': hasResults }">
        <input type="text" class="search-input">
        <ul class="search-results" v-if="hasResults">
            <li>
                <strong>key</strong>
                <p>Value goes here</p>
            </li>
            <li>
                <strong>key</strong>
                <p>Value goes here</p>
            </li>
            <li>
                <strong>key</strong>
                <p>Value goes here</p>
            </li>
            <li>
                <strong>key</strong>
                <p>Value goes here</p>
            </li>
            <li>
                <strong>key</strong>
                <p>Value goes here</p>
            </li>
        </ul>
    </div>
</template>

<script>
    export default {
        data: function() {
            return {
                hasResults: false
            }
        }
    }
</script>