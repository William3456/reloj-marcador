@props(['disabled' => false])

<input @disabled($disabled)
    {{ $attributes->merge([
        'class' =>
            'rounded-md shadow-sm ' .
            ($errors->has($attributes->get('name'))
                ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500')
    ]) }}>

@error($attributes->get('name'))
    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
@enderror
