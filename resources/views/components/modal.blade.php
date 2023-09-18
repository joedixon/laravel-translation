<div
    class="fixed inset-0 z-30 flex items-center justify-center overflow-auto bg-black bg-opacity-50"
    x-show="modal === '{{ $name }}'"
>
    <div class="min-w-[600px] min-h-[400px] max-w-3xl px-6 py-4 mx-auto text-left bg-white rounded shadow-lg" @click.outside="modal = false">
        {{ $slot }}
    </div>
</div>