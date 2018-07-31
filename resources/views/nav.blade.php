<nav class="header">

    <h1 class="text-lg px-4">{{ config('app.name') }}</h1>

    <ul class="flex flex-grow justify-end">
        <li>
            <a href="#" class="active">
                @include('translation::icons.globe')
                {{ __('translation::translation.languages') }}
            </a>
        </li>
        <li>
            <a href="#">
                @include('translation::icons.translate')
                {{ __('translation::translation.translations') }}
            </a>
        </li>
    </ul>

    {{--<li>
        <select>
            @foreach($languages as $language)
                <option>{{ $language }}</option>
            @endforeach
        </select>
    </li>
    <li>
        <select>
            @foreach($groups as $group)
                <option>{{ $group }}</option>
            @endforeach
        </select>
    </li>
    <li>{{ $language }}</li>
    <li>{{ $file }}</li>--}}

</nav>