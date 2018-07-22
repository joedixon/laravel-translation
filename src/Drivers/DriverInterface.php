<?php

namespace JoeDixon\Translation\Drivers;

interface DriverInterface
{
    public function allLanguages();

    public function allTranslations();

    public function allTranslationsFor($language);

    public function addLanguage($language);

    public function addJsonTranslation($language, $key, $value = '');

    public function addArrayTranslation($language, $key, $value = '');
}
