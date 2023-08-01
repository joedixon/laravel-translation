<div class="w-full p-4">
    <div class="flex gap-x-5">
        <select wire:model="language" wire:change="changeLanguage">
            @foreach ($languages as $selectLanguage)
                <option value="{{ $selectLanguage }}">{{ $selectLanguage }}</option>
            @endforeach
        </select>

        <input type="search" placeholder="Search">

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
                    @foreach ($shortKeys as $group => $translations)
                        @foreach ($translations as $key => $value)
                            @if (! (isset($shortKeyTranslations[$group][$key]) && is_array($shortKeyTranslations[$group][$key])))
                                <tr wire:key="short-{{ Str::slug($key) }}">
                                    <td>
                                        {{ Str::contains($group, '::') ? Str::before($group, '::') . ' / ' : '' }}{{ Str::before(Str::after($group, '::'), '.') }}
                                    </td>

                                    <td>{{ $key }}</td>

                                    <td>
                                        <input 
                                            type="text" 
                                            value="{{ $shortKeyTranslations[$group][$key] ?? null }}" 
                                            wire:change="translateShortKey('{{ Str::before(Str::after($group, '::'), '.') }}', '{{ $key }}', $event.target.value, '{{ Str::contains($group, '::') ? Str::before($group, '::') : null }}')"
                                        >
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    @endforeach
                </tbody>
            @else
                <tbody>
                    @foreach ($stringKeys as $key => $value)
                        @if (is_array($value))
                            @foreach ($value as $k => $v)
                                <tr wire:key="string-{{ Str::slug($k) }}">
                                    <td>{{ $key }}</td>

                                    <td>{{ $k }}</td>

                                    <td>
                                        <input 
                                            type="text" 
                                            value="{{ $stringKeyTranslations[$key][$k] ?? null }}" 
                                            wire:change="translateStringKey('{{ $k }}', $event.target.value, '{{ $key }}')"
                                        >
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr wire:key="string-{{ Str::slug($key) }}">
                                <td>root</td>

                                <td>{{ $key }}</td>

                                <td>
                                    <input 
                                        type="text" 
                                        value="{{ $stringKeyTranslations[$key] ?? null }}" 
                                        wire:change="translateStringKey('{{ $key }}', $event.target.value)"
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