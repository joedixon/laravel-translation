# Laravel Translation Manager

![Laravel Translation Manager](translation.png)

![Travis (.org) branch](https://img.shields.io/travis/joedixon/laravel-translation/master.svg?style=for-the-badge)
![Scrutinizer](https://img.shields.io/scrutinizer/g/joedixon/laravel-translation.svg?style=for-the-badge)
![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/joedixon/laravel-translation.svg?style=for-the-badge)

## Installation

Install the package via Composer

`composer require joedixon/laravel-translation`

Publish configuration and assets

`php artisan vendor:publish
--provider=JoeDixon\\Translation\\TranslationServiceProvider`

## Usage

Navigate to http://your-project.dev/translation and use the interface to manage
your translations.

First, click on the locale you wish to edit. On the subsequent page, find the
translation you want to edit and click on the pencil icon or on the text and
make your edits. As soon as you remove focus from the input, your translation
will be saved, indicated by the green check icon. 

