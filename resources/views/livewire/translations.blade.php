<div class="w-full flex flex-col">
    <div class="flex items-center justify-between gap-x-5 p-4 bg-indigo-600">
        <div class="flex items-center gap-x-5">
            <select wire:model="language" wire:model.live="language" class="placeholder:text-white/100 text-white/100 bg-white/20 py-2 px-3 rounded text-sm focus:bg-white/100 focus:text-gray-700">
                @foreach ($languages as $selectLanguage)
                    <option value="{{ $selectLanguage }}">{{ $selectLanguage }}</option>
                @endforeach
            </select>

            <select wire:model.live="type" class="placeholder:text-white/100 text-white/100 bg-white/20 py-2 px-3 rounded text-sm focus:bg-white/100 focus:text-gray-700">
                <option value="short">Short Key Translations</option>

                <option value="string">String Key Translations</option>
            </select>

            <div class="relative min-w-[320px]">
                <input 
                    type="search" 
                    placeholder="Search" 
                    wire:model.live.debounce.150ms="query" 
                    class="peer w-full placeholder:text-white/100 text-white/100 bg-white/20 py-2 px-3 pr-10 rounded text-sm focus:bg-white/100 focus:text-gray-700 focus:placeholder:text-gray-700/100"
                >

                <x-translation::icons.magnifying-glass class="absolute my-2 mx-2 w-5 h-5 text-white/100 peer-focus:text-gray-700 right-0 top-0" />
            </div>

            <span class="text-white/50 text-sm">
                Showing {{ $count = $this->translations->flatten()->count() }} {{ Str::plural('result', $count) }}
            </span>

            <span wire:loading>
                <x-translation::loader />
            </span>
        </div>

        <div class="flex items-center gap-x-5">
            <button class="text-sm text-white bg-indigo-700 py-2 px-4 rounded hover:bg-indigo-900/60 transition">
                Add Language
            </button>

            <button class="text-sm text-white bg-indigo-700 py-2 px-4 rounded hover:bg-indigo-900/60 transition">
                Add Translation
            </button>
        </div>
    </div>

    <div class="w-full">
        <table class="w-full">
            <thead class="bg-indigo-50 text-gray-500 text-sm uppercase border-b border-indigo-200">
                <tr>
                    <th class="text-left min-w-[20%] px-4 py-3">Vendor / Group</th>
                    <th class="text-left min-w-[20%] px-4 py-3">Key</th>
                    <th class="text-left w-full px-4 py-3">Value</th>
                </tr>
            </thead>

            @if ($type === 'short')
                <tbody>
                    @foreach ($this->translations as $group => $this->translations)
                        @foreach ($this->translations as $key => $value)
                            @if (! is_array($value))
                                <tr class="border-b border-gray-100 text-gray-500 font-light hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        {{ Str::contains($group, '::') ? Str::before($group, '::') . ' / ' : '' }}{{ Str::replace('/', ' / ', Str::after($group, '::')) }}
                                    </td>

                                    <td class="px-4 py-3">{{ $key }}</td>

                                    <td class="px-4 py-3">
                                        <textarea 
                                            wire:change="translateShortKey('{{ Str::before(Str::after($group, '::'), '.') }}', '{{ $key }}', $event.target.value, '{{ Str::contains($group, '::') ? Str::before($group, '::') : null }}')"
                                            wire:key="short-{{ Str::slug($key) }}"
                                            class="w-full px-2 py-1 bg-gray-50 rounded text-gray-700"
                                            placeholder="-"
                                            rows="1"
                                        >{{ $value }}</textarea>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    @endforeach
                </tbody>
            @else
                <tbody>
                    @foreach ($this->translations as $key => $value)
                        @if (is_array($value))
                            @foreach ($value as $k => $v)
                                <tr class="border-b border-gray-100 text-gray-500 font-light">
                                    <td class="px-4 py-3">{{ $key }}</td>

                                    <td class="px-4 py-3">{{ $k }}</td>

                                    <td class="px-4 py-3">
                                        <textarea 
                                            wire:change="translateStringKey('{{ $k }}', $event.target.value, '{{ $key }}')"
                                            wire:key="string-{{ Str::slug($k) }}"
                                            class="w-full px-2 py-1 bg-gray-50 rounded text-gray-700"
                                            placeholder="-"
                                            rows="1"
                                        >{{ $value }}</textarea>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td class="px-4 py-3">root</td>

                                <td class="px-4 py-3">{{ $key }}</td>

                                <td class="px-4 py-3">
                                    <textarea 
                                        wire:change="translateStringKey('{{ $key }}', $event.target.value)"
                                        wire:key="string-{{ Str::slug($key) }}"
                                        class="w-full px-2 py-1 bg-gray-50 rounded text-gray-700"
                                        placeholder="-"
                                        rows="1"
                                    >{{ $value }}</textarea>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            @endif
        </table>
    </div>
</div>