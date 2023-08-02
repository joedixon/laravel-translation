<div class="w-full p-4">
    <div class="flex gap-x-5">
        <select wire:model="language" wire:model.live="language">
            @foreach ($languages as $selectLanguage)
                <option value="{{ $selectLanguage }}">{{ $selectLanguage }}</option>
            @endforeach
        </select>

        <input type="search" placeholder="Search" wire:model.live.debounce.150ms="query">

        <select wire:model.live="type">
            <option value="short">Short Key Translations</option>

            <option value="string">String Key Translations</option>
        </select>
    </div>

    <div>
        <table>
            <thead>
                <tr>
                    <th>Vendor / Group</th>
                    <th>Key</th>
                    <th>Value</th>
                </tr>
            </thead>

            @if ($type === 'short')
                <tbody>
                    @foreach ($this->translations as $group => $this->translations)
                        @foreach ($this->translations as $key => $value)
                            @if (! is_array($value))
                                <tr>
                                    <td>
                                        {{ Str::contains($group, '::') ? Str::before($group, '::') . ' / ' : '' }}{{ Str::before(Str::after($group, '::'), '.') }}
                                    </td>

                                    <td>{{ $key }}</td>

                                    <td>
                                        <input 
                                            type="text" 
                                            value="{{ $value }}" 
                                            wire:change="translateShortKey('{{ Str::before(Str::after($group, '::'), '.') }}', '{{ $key }}', $event.target.value, '{{ Str::contains($group, '::') ? Str::before($group, '::') : null }}')"
                                            wire:key="short-{{ Str::slug($key) }}"
                                        >
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
                                <tr>
                                    <td>{{ $key }}</td>

                                    <td>{{ $k }}</td>

                                    <td>
                                        <input 
                                            type="text" 
                                            value="{{ $v }}" 
                                            wire:change="translateStringKey('{{ $k }}', $event.target.value, '{{ $key }}')"
                                            wire:key="string-{{ Str::slug($k) }}"
                                        >
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>root</td>

                                <td>{{ $key }}</td>

                                <td>
                                    <input 
                                        type="text" 
                                        value="{{ $value }}" 
                                        wire:change="translateStringKey('{{ $key }}', $event.target.value)"
                                        wire:key="string-{{ Str::slug($key) }}"
                                    >
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            @endif
        </table>
    </div>
</div>