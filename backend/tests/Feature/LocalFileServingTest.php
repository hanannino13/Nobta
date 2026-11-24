<?php

use Illuminate\Support\Facades\Route;

/*
| The private `local` disk must not be served over HTTP: no `storage.local` GET/PUT routes exist,
| and requests to /storage/... do not reach any file. No files are created, read or deleted here.
*/

it('does not register the local disk serve or upload routes', function () {
    expect(config('filesystems.disks.local.serve'))->toBeFalse()
        ->and(Route::has('storage.local'))->toBeFalse()
        ->and(Route::has('storage.local.upload'))->toBeFalse();
});

it('does not expose files under /storage', function () {
    // `.gitignore` really exists in storage/app/private; it must still not be reachable.
    $this->getJson('/storage/.gitignore')->assertNotFound();
    $this->getJson('/storage/private/.gitignore')->assertNotFound();
});

it('does not accept uploads under /storage', function () {
    $this->putJson('/storage/uploaded.txt?upload=1', ['content' => 'x'])->assertNotFound();
});
