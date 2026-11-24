<?php

/*
| Architecture rules enforced from day one (see docs/architecture/ARCHITECTURE_DECISIONS.md).
| More rules (module boundaries, tenant model conventions) are added as modules land in M2+.
*/

arch('no debugging helpers are left in application code')
    ->expect(['dd', 'dump', 'ddd', 'ray', 'var_dump', 'print_r', 'die', 'exit'])
    ->not->toBeUsed();

arch('PHP security preset')->preset()->security();

arch('controllers do not query the database directly via the DB facade')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('models extend Eloquent')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');
