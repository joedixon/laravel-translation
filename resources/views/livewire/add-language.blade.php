<x-translation::modal name="add-language">
    <h2>Add a new language</h2>

    <form wire:submit="add" @submit="modal = false">
        <input wire:model="language" type="text" placeholder="en_US" class="w-full px-2 py-1 bg-gray-50 rounded text-gray-700 group-hover:bg-white focus:bg-white">

        <button type="submit">Add</button>
    </form>
</x-translation::modal>