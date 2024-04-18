<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute harus diterima.',
    'accepted_if' => ':attribute harus diterima ketika :other adalah :value.',
    'active_url' => ':attribute bukan URL yang valid.',
    'after' => ':attribute harus tanggal setelah :date.',
    'after_or_equal' => ':attribute harus tanggal setelah atau sama dengan tanggal :date.',
    'alpha' => ':attribute hanya boleh berisi huruf.',
    'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => ':attribute hanya boleh berisi huruf dan angka.',
    'array' => ':attribute harus berupa array.',
    'before' => ':attribute harus tanggal sebelum :date.',
    'before_or_equal' => ':attribute harus tanggal sebelum atau sama dengan tanggal :date.',
    'between' => [
        'array' => ':attribute harus diantara :min dan :max.',
        'file' => ':attribute harus diantara :min dan :max kilobytes.',
        'numeric' => ':attribute harus diantara :min dan :max.',
        'string' => ':attribute harus diantara :min dan :max karakter.',
    ],
    'boolean' => ':attribute hanya bisa diisi true atau false.',
    'confirmed' => ':attribute konfirmasi tidak cocok.',
    'current_password' => 'password salah.',
    'date' => ':attribute bukan tanggal yang valid.',
    'date_equals' => ':attribute harus tanggal yang sama dengan :date.',
    'date_format' => ':attribute tidak sesuai dengan format :format.',
    'declined' => ':attribute harus ditolak.',
    'declined_if' => ':attribute harus ditolak ketika :other adalah :value.',
    'different' => ':attribute dan :other harus berbeda.',
    'digits' => ':attribute harus :digits digit.',
    'digits_between' => ':attribute harus diantara :min and :max digit.',
    'dimensions' => ':attribute memiliki dimensi gambar yang tidak valid.',
    'distinct' => ':attribute memiliki nilai duplikat.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'ends_with' => ':attribute harus diakhiri dengan salah satu dari berikut ini: :values.',
    'enum' => ':attribute yang dipilih tidak valid.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'file' => ':attribute harus berupa file.',
    'filled' => ':attribute harus diisi.',
    'gt' => [
        'array' => ':attribute harus lebih dari :value item.',
        'file' => ':attribute harus lebih besar dari :value kilobytes.',
        'numeric' => ':attribute harus lebih besar dari :value.',
        'string' => ':attribute harus lebih besar dari :value karakter.',
    ],
    'gte' => [
        'array' => ':attribute harus :value item atau lebih.',
        'file' => ':attribute harus lebih besar atau sama dengan :value kilobytes.',
        'numeric' => ':attribute harus lebih besar atau sama dengan :value.',
        'string' => ':attribute harus lebih besar atau sama dengan :value karakter.',
    ],
    'image' => ':attribute harus sebuag gambar.',
    'in' => ':attribute yang dipilih tidak valid (pilihan yang tersedia: :values).',
    'in_array' => ':attribute tidak tersedia di :other.',
    'integer' => ':attribute harus berupa integer.',
    'ip' => ':attribute harus berupa IP address yang valid.',
    'ipv4' => ':attribute harus berupa IPv4 address yang valid.',
    'ipv6' => ':attribute harus berupa IPv6 address yang valid.',
    'json' => ':attribute harus berupa JSON string yang valid.',
    'lt' => [
        'array' => ':attribute harus kurang dari :value item.',
        'file' => ':attribute harus lebih kecil dari :value kilobytes.',
        'numeric' => ':attribute harus lebih kecil dari :value.',
        'string' => ':attribute harus lebih kecil dari :value karakter.',
    ],
    'lte' => [
        'array' => ':attribute harus kurang dari :value items.',
        'file' => ':attribute harus lebih kecil atau sama dengan :value kilobytes.',
        'numeric' => ':attribute harus lebih kecil atau sama dengan :value.',
        'string' => ':attribute harus lebih kecil atau sama dengan :value karakter.',
    ],
    'mac_address' => ':attribute harus berupa MAC address yang valid.',
    'max' => [
        'array' => ':attribute tidak boleh lebih dari :max item.',
        'file' => ':attribute tidak boleh lebih besar dari :max kilobytes.',
        'numeric' => ':attribute tidak boleh lebih besar dari :max.',
        'string' => ':attribute tidak boleh lebih besar dari :max karakter.',
    ],
    'mimes' => ':attribute harus berupa file dengan tipe: :values.',
    'mimetypes' => ':attribute harus berupa file dengan tipe: :values.',
    'min' => [
        'array' => ':attribute setidaknya harus memiliki :min item.',
        'file' => ':attribute setidaknya harus :min kilobytes.',
        'numeric' => ':attribute setidaknya harus :min.',
        'string' => ':attribute setidaknya harus :min karakter.',
    ],
    'multiple_of' => ':attribute harus kelipatan dari :value.',
    'not_in' => ':attribute yang dipilih tidak valid.',
    'not_regex' => ':attribute format tidak valid.',
    'numeric' => ':attribute harus berupa angka.',
    'password' => ':attribute salah.',
    'present' => ':attribute harus ada.',
    'prohibited' => ':attribute dilarang.',
    'prohibited_if' => ':attribute dilarang ketika :other adalah :value.',
    'prohibited_unless' => ':attribute dilarang kecuali :other adalah :values.',
    'prohibits' => ':attribute melarang :other.',
    'regex' => ':attribute format tidak valid.',
    'required' => ':attribute harus diisi.',
    'required_array_keys' => ':attribute harus berisi: :values.',
    'required_if' => ':attribute harus diisi ketika :other adalah :value.',
    'required_unless' => ':attribute harus diisi kecuali :other adalah :values.',
    'required_with' => ':attribute harus diisi ketika :values.',
    'required_with_all' => ':attribute harus diisi ketika :values.',
    'required_without' => ':attribute harus diisi ketika tidak :values.',
    'required_without_all' => ':attribute harus diisi ketika tidak :values.',
    'same' => 'nilai :attribute dan :other harus sama.',
    'size' => [
        'array' => ':attribute harus berisi :size item.',
        'file' => ':attribute harus :size kilobytes.',
        'numeric' => ':attribute harus :size.',
        'string' => ':attribute harus :size karakter.',
    ],
    'starts_with' => ':attribute harus dimulai dengan: :values.',
    'string' => ':attribute harus berupa string.',
    'timezone' => ':attribute harus berupa timezone yang valid.',
    'unique' => ':attribute sudah diambil.',
    'uploaded' => ':attribute gagal mengunggah.',
    'url' => ':attribute harus sebual URL yang valid.',
    'uuid' => ':attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
