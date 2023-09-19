<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    {{ Vite::useBuildDirectory('vendor/translation') }}
    @vite('resources/css/translation.css')
</head>
<body>
    <div>
        @include('translation::notifications')
        
        {{ $slot }}
    </div>
</body>
</html>
