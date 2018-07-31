@extends('translation::layout')

@section('body')

    @if(count($translations))

        <div class="panel">

            <div class="panel-header">

                {{ __('translation::translation.languages') }}

                <div class="flex flex-grow justify-end">

                    <a href="#" class="button">+ Add</a>

                </div>

            </div>

            <div class="panel-body">

                <table>

                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Base Value ({{ config('app.locale') }})</th>
                            <th>Value</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($translations as $key => $values)
                            <tr>
                                <td>{{ $key }}</td>
                                <td>{{ $values[0] }}</td>
                                <td>
                                    <translation-input translation="{{ $values[1] }}"></translation-input>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>

        </div>

    @endif

@endsection