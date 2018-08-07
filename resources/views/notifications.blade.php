@if(Session::has('success'))
    <div class="bg-green-lightest text-green-darker p-6 shadow-md" role="alert">
        <div class="flex justify-center">
            <p>{{ Session::get('success') }}</p>
        </div>
    </div>
@endif

@if(Session::has('error'))
    <div class="bg-red-lightest text-red-darker p-6 shadow-md" role="alert">
        <div class="flex justify-center">
            <p>{!! Session::get('error') !!}</p>
        </div>
    </div>
@endif