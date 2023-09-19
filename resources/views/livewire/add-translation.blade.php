<x-translation::modal name="add-translation">
    <h2>Add a new language</h2>

    <form wire:submit="add" @submit="modal = false" class="flex flex-col gap-y-4">
        <select wire:model="type" class="w-full px-2 py-1 bg-gray-50 rounded text-gray-700 group-hover:bg-white focus:bg-white">
            <option value="short">Short</option>

            <option value="string">String</option>
        </select>

        <input wire:model="group" type="text" name="group" placeholder="validation" class="w-full px-2 py-1 bg-gray-50 rounded text-gray-700 group-hover:bg-white focus:bg-white" x-show="$wire.type == 'short'">

        <input wire:model="key" type="text" name="key" placeholder="required" class="w-full px-2 py-1 bg-gray-50 rounded text-gray-700 group-hover:bg-white focus:bg-white">

        <input wire:model="value" type="text" name="value" placeholder="The :attribute is required" class="w-full px-2 py-1 bg-gray-50 rounded text-gray-700 group-hover:bg-white focus:bg-white">

        <input wire:model="vendor" type="text" name="vendor" placeholder="my-package" class="w-full px-2 py-1 bg-gray-50 rounded text-gray-700 group-hover:bg-white focus:bg-white">

        <button type="submit">Add</button>
    </form>
</x-translation::modal>