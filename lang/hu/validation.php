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

    'accepted' => 'A(z) :attribute el kell legyen fogadva.',
    'accepted_if' => 'A(z) :attribute el kell legyen fogadva, ha a(z) :other értéke :value.',
    'active_url' => 'A(z) :attribute nem érvényes URL.',
    'after' => 'A(z) :attribute :date utáni dátum kell, hogy legyen.',
    'after_or_equal' => 'A(z) :attribute :date utáni vagy azzal egyenlő dátum kell, hogy legyen.',
    'alpha' => 'A(z) :attribute kizárólag betűket tartalmazhat.',
    'alpha_dash' => 'A(z) :attribute kizárólag betűket, számokat, kötőjeleket és alulvonásokat tartalmazhat.',
    'alpha_num' => 'A(z) :attribute kizárólag betűket és számokat tartalmazhat.',
    'array' => 'A(z) :attribute egy tömb kell, hogy legyen.',
    'ascii' => 'A(z) :attribute csak alfanumerikus karaktereket és szimbólumokat tartalmazhat.',
    'before' => 'A(z) :attribute :date előtti dátum kell, hogy legyen.',
    'before_or_equal' => 'A(z) :attribute :date előtti vagy azzal egyenlő dátum kell, hogy legyen.',
    'between' => [
        'array' => 'A(z) :attribute :min - :max közötti elemet kell, hogy tartalmazzon.',
        'file' => 'A(z) :attribute mérete :min és :max kilobájt között kell, hogy legyen.',
        'numeric' => 'A(z) :attribute :min és :max között kell, hogy legyen.',
        'string' => 'A(z) :attribute hossza :min és :max karakter között kell, hogy legyen.',
    ],
    'boolean' => 'A(z) :attribute mező csak true vagy false értéket vehet fel.',
    'can' => 'A(z) :attribute mező nem engedélyezett értéket tartalmaz.',
    'confirmed' => 'A(z) :attribute megerősítése nem egyezik.',
    'current_password' => 'A jelszó helytelen.',
    'date' => 'A(z) :attribute nem érvényes dátum.',
    'date_equals' => 'A(z) :attribute meg kell egyezzen a következővel: :date.',
    'date_format' => 'A(z) :attribute nem egyezik az alábbi dátum formátummal: :format.',
    'decimal' => 'A(z) :attribute :decimal tizedesjegyet kell tartalmazzon.',
    'declined' => 'A(z) :attribute el kell legyen utasítva.',
    'declined_if' => 'A(z) :attribute el kell legyen utasítva, ha a(z) :other értéke :value.',
    'different' => 'A(z) :attribute és :other értékei különbözőek kell, hogy legyenek.',
    'digits' => 'A(z) :attribute :digits számjegyű kell, hogy legyen.',
    'digits_between' => 'A(z) :attribute értéke :min és :max közötti számjegy lehet.',
    'dimensions' => 'A(z) :attribute felbontása nem megfelelő.',
    'distinct' => 'A(z) :attribute értékének egyedinek kell lennie.',
    'doesnt_end_with' => 'A(z) :attribute nem végződhet a következőkkel: :values.',
    'doesnt_start_with' => 'A(z) :attribute nem kezdődhet a következőkkel: :values.',
    'email' => 'A(z) :attribute nem érvényes email formátum.',
    'ends_with' => 'A(z) :attribute a következővel kell, hogy végződjön: :values.',
    'enum' => 'A kiválasztott :attribute érvénytelen.',
    'exists' => 'A kiválasztott :attribute érvénytelen.',
    'extensions' => 'A(z) :attribute kiterjesztése a következők egyike kell legyen: :values.',
    'file' => 'A(z) :attribute fájl kell, hogy legyen.',
    'filled' => 'A(z) :attribute megadása kötelező.',
    'gt' => [
        'array' => 'A(z) :attribute több, mint :value elemet kell, hogy tartalmazzon.',
        'file' => 'A(z) :attribute mérete nagyobb kell, hogy legyen, mint :value kilobájt.',
        'numeric' => 'A(z) :attribute nagyobb kell, hogy legyen, mint :value.',
        'string' => 'A(z) :attribute hosszabb kell, hogy legyen, mint :value karakter.',
    ],
    'gte' => [
        'array' => 'A(z) :attribute legalább :value elemet kell, hogy tartalmazzon.',
        'file' => 'A(z) :attribute mérete nem lehet kevesebb, mint :value kilobájt.',
        'numeric' => 'A(z) :attribute nagyobb vagy egyenlő kell, hogy legyen, mint :value.',
        'string' => 'A(z) :attribute hossza nem lehet kevesebb, mint :value karakter.',
    ],
    'hex_color' => 'A(z) :attribute érvényes hexadecimális színkód kell, hogy legyen.',
    'image' => 'A(z) :attribute képfájl kell, hogy legyen.',
    'in' => 'A kiválasztott :attribute érvénytelen.',
    'in_array' => 'A(z) :attribute értéke nem található a(z) :other értékek között.',
    'integer' => 'A(z) :attribute értéke szám kell, hogy legyen.',
    'ip' => 'A(z) :attribute érvényes IP cím kell, hogy legyen.',
    'ipv4' => 'A(z) :attribute érvényes IPv4 cím kell, hogy legyen.',
    'ipv6' => 'A(z) :attribute érvényes IPv6 cím kell, hogy legyen.',
    'json' => 'A(z) :attribute érvényes JSON szöveg kell, hogy legyen.',
    'list' => 'A(z) :attribute lista kell, hogy legyen.',
    'lowercase' => 'A(z) :attribute kisbetűs kell, hogy legyen.',
    'lt' => [
        'array' => 'A(z) :attribute kevesebb, mint :value elemet kell, hogy tartalmazzon.',
        'file' => 'A(z) :attribute mérete kisebb kell, hogy legyen, mint :value kilobájt.',
        'numeric' => 'A(z) :attribute kisebb kell, hogy legyen, mint :value.',
        'string' => 'A(z) :attribute rövidebb kell, hogy legyen, mint :value karakter.',
    ],
    'lte' => [
        'array' => 'A(z) :attribute legfeljebb :value elemet kell, hogy tartalmazzon.',
        'file' => 'A(z) :attribute mérete nem lehet több, mint :value kilobájt.',
        'numeric' => 'A(z) :attribute kisebb vagy egyenlő kell, hogy legyen, mint :value.',
        'string' => 'A(z) :attribute hossza nem lehet több, mint :value karakter.',
    ],
    'mac_address' => 'A(z) :attribute érvényes MAC cím kell, hogy legyen.',
    'max' => [
        'array' => 'A(z) :attribute legfeljebb :max elemet tartalmazhat.',
        'file' => 'A(z) :attribute mérete nem lehet több, mint :max kilobájt.',
        'numeric' => 'A(z) :attribute nem lehet nagyobb, mint :max.',
        'string' => 'A(z) :attribute hossza nem lehet több, mint :max karakter.',
    ],
    'max_digits' => 'A(z) :attribute legfeljebb :max számjegyű lehet.',
    'mimes' => 'A(z) :attribute kiterjesztése a következők egyike kell legyen: :values.',
    'mimetypes' => 'A(z) :attribute kiterjesztése a következők egyike kell legyen: :values.',
    'min' => [
        'array' => 'A(z) :attribute legalább :min elemet kell, hogy tartalmazzon.',
        'file' => 'A(z) :attribute mérete nem lehet kevesebb, mint :min kilobájt.',
        'numeric' => 'A(z) :attribute legalább :min kell, hogy legyen.',
        'string' => 'A(z) :attribute hossza nem lehet kevesebb, mint :min karakter.',
    ],
    'min_digits' => 'A(z) :attribute legalább :min számjegyű kell, hogy legyen.',
    'missing' => 'A(z) :attribute mező nem szerepelhet.',
    'missing_if' => 'A(z) :attribute mező nem szerepelhet, ha a(z) :other értéke :value.',
    'missing_unless' => 'A(z) :attribute mező nem szerepelhet, kivéve, ha a(z) :other értéke :value.',
    'missing_with' => 'A(z) :attribute mező nem szerepelhet, ha a(z) :values jelen van.',
    'missing_with_all' => 'A(z) :attribute mező nem szerepelhet, ha a(z) :values jelen van.',
    'multiple_of' => 'A(z) :attribute :value többszöröse kell, hogy legyen.',
    'not_in' => 'A kiválasztott :attribute érvénytelen.',
    'not_regex' => 'A(z) :attribute formátuma érvénytelen.',
    'numeric' => 'A(z) :attribute szám kell, hogy legyen.',
    'password' => [
        'letters' => 'A(z) :attribute tartalmaznia kell legalább egy betűt.',
        'mixed' => 'A(z) :attribute tartalmaznia kell legalább egy nagybetűt és egy kisbetűt.',
        'numbers' => 'A(z) :attribute tartalmaznia kell legalább egy számot.',
        'symbols' => 'A(z) :attribute tartalmaznia kell legalább egy szimbólumot.',
        'uncompromised' => 'A megadott :attribute már szerepelt egy adatvédelmi incidensben. Kérjük, válasszon másikat.',
    ],
    'present' => 'A(z) :attribute mező nem található.',
    'present_if' => 'A(z) :attribute mezőnek jelen kell lennie, ha a(z) :other értéke :value.',
    'present_unless' => 'A(z) :attribute mezőnek jelen kell lennie, kivéve, ha a(z) :other értéke :value.',
    'present_with' => 'A(z) :attribute mezőnek jelen kell lennie, ha a(z) :values jelen van.',
    'present_with_all' => 'A(z) :attribute mezőnek jelen kell lennie, ha a(z) :values jelen van.',
    'prohibited' => 'A(z) :attribute mező tiltott.',
    'prohibited_if' => 'A(z) :attribute mező tiltott, ha a(z) :other értéke :value.',
    'prohibited_unless' => 'A(z) :attribute mező tiltott, kivéve, ha a(z) :other értéke :values.',
    'prohibits' => 'A(z) :attribute mező tiltja, hogy a(z) :other jelen legyen.',
    'regex' => 'A(z) :attribute formátuma érvénytelen.',
    'required' => 'A(z) :attribute mező kitöltése kötelező.',
    'required_array_keys' => 'A(z) :attribute mezőnek tartalmaznia kell a következőket: :values.',
    'required_if' => 'A(z) :attribute mező kitöltése kötelező, ha a(z) :other értéke :value.',
    'required_if_accepted' => 'A(z) :attribute mező kitöltése kötelező, ha a(z) :other el van fogadva.',
    'required_unless' => 'A(z) :attribute mező kitöltése kötelező, kivéve, ha a(z) :other értéke :values.',
    'required_with' => 'A(z) :attribute mező kitöltése kötelező, ha a(z) :values jelen van.',
    'required_with_all' => 'A(z) :attribute mező kitöltése kötelező, ha a(z) :values jelen van.',
    'required_without' => 'A(z) :attribute mező kitöltése kötelező, ha a(z) :values nincs jelen.',
    'required_without_all' => 'A(z) :attribute mező kitöltése kötelező, ha egyik :values sem található.',
    'same' => 'A(z) :attribute és :other mezőknek egyezniük kell.',
    'size' => [
        'array' => 'A(z) :attribute :size elemet kell, hogy tartalmazzon.',
        'file' => 'A(z) :attribute mérete :size kilobájt kell, hogy legyen.',
        'numeric' => 'A(z) :attribute értéke :size kell, hogy legyen.',
        'string' => 'A(z) :attribute hossza :size karakter kell, hogy legyen.',
    ],
    'starts_with' => 'A(z) :attribute a következők egyikével kell, hogy kezdődjön: :values.',
    'string' => 'A(z) :attribute szöveg kell, hogy legyen.',
    'timezone' => 'A(z) :attribute nem érvényes időzóna.',
    'unique' => 'A(z) :attribute már foglalt.',
    'uploaded' => 'A(z) :attribute feltöltése sikertelen.',
    'uppercase' => 'A(z) :attribute nagybetűs kell, hogy legyen.',
    'url' => 'A(z) :attribute érvénytelen link.',
    'ulid' => 'A(z) :attribute érvényes ULID kell, hogy legyen.',
    'uuid' => 'A(z) :attribute érvényes UUID kell, hogy legyen.',

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

    'attributes' => [
        'name' => 'Név',
        'email' => 'Email cím',
        'password' => 'Jelszó',
        'role' => 'Szerepkör',
        'date' => 'Dátum',
        'type' => 'Típus',
        'description' => 'Leírás',
        'allowance' => 'Keret',
        'used' => 'Felhasznált',
        'start_date' => 'Kezdő dátum',
        'end_date' => 'Végdátum',
        'reason' => 'Indoklás',
        'selectedDate' => 'Kiválasztott dátum',
        'endDate' => 'Végdátum',
        'requestType' => 'Igénylés típusa',
        'userId' => 'Felhasználó',
    ],

];
