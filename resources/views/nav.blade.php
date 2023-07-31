<nav class="header">

    <h1 class="text-lg px-6">{{ config('app.name') }}</h1>

    <ul class="flex-grow justify-end pr-2">
        <li>
            <a href="{{ route('languages.index') }}">
                @include('translation::icons.globe')
                {{ __('translation::translation.languages') }}
            </a>
        </li>
        <li>
            <a href="{{ route('languages.translations.index', config('app.locale')) }}">
                @include('translation::icons.translate')
                {{ __('translation::translation.translations') }}
            </a>
        </li>
    </ul>

</nav>