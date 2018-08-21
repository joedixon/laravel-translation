@extends('translation::layout')

@section('body')

    @if(count($languages))

        <div class="panel w-1/2">

            <div class="panel-header">

                {{ __('translation::translation.languages') }}

                <div class="flex flex-grow justify-end">

                    <a href="{{ route('languages.create') }}" class="button">
                        {{ __('translation::translation.add') }}
                    </a>

                </div>

            </div>

            <div class="panel-body">

                <table>

                    <thead>
                        <tr>
                            <th>{{ __('translation::translation.language_name') }}</th>
                            <th>{{ __('translation::translation.locale') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($languages as $language)
                            <tr>
                                <td>
                                    {{ data_get($language, 'name', $language) }}
                                </td>
                                <td>
                                    <a href="{{ route('languages.translations.index', $language) }}">
                                        {{ $language }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>

        </div>

    @endif

@endsection