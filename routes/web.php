<?php

use App\Http\Controllers\InvitationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/invitations/{invitation}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('/invitations/{invitation}/accept', [InvitationController::class, 'accept'])->name('invitations.accept');
Route::post('/invitations/{invitation}', [InvitationController::class, 'store'])->name('invitations.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('groups', 'pages::group.index')->name('groups.index');
    Route::middleware('can:view,group')->group(function () {
        // Groups
        Route::livewire('groups/{group}', 'pages::group.show')
            ->name('groups.show');

        Route::livewire('groups/{group}/members', 'pages::group.members')
            ->name('groups.members')
            ->can('manage-members', 'group');

        // Places
        Route::livewire('groups/{group}/places/{place}', 'pages::places.show')
            ->name('places.show')
            ->can('view', 'place');

        Route::livewire('groups/{group}/places/{place}/add-integration', 'pages::integration.create')
            ->name('integrations.create')
            ->can('manage-integrations', 'place');

        Route::livewire('groups/{group}/places/{place}/manage-allocations', 'pages::places.manage-allocations')
            ->name('places.manage-allocations')
            ->can('manage-allocations', 'place');
    });
});

require __DIR__.'/settings.php';
