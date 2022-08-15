let mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application, as well as bundling up your JS files.
 |
 */

mix.setPublicPath('public/assets');

mix.postCss('resources/assets/css/main.css', 'css', [
    require('tailwindcss'),
]).js('resources/assets/js/app.js', 'js').vue()