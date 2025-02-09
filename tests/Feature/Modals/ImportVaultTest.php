<?php

declare(strict_types=1);

use App\Actions\CreateVault;
use App\Livewire\Modals\ImportVault;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

it('opens the modal', function (): void {
    $user = User::factory()->create()->first();

    Livewire::actingAs($user)
        ->test(ImportVault::class)
        ->assertSet('show', false)
        ->call('open')
        ->assertSet('show', true);
});

it('imports a zip file', function (): void {
    $user = User::factory()->create()->first();
    $file = UploadedFile::fake()->create('test.zip');

    Livewire::actingAs($user)
        ->test(ImportVault::class)
        ->set('file', $file)
        ->assertSet('show', false);
});

it('handles name collisions when importing a vault with an existing name', function (): void {
    $user = User::factory()->create()->first();
    $vaultName = fake()->words(3, true);
    new CreateVault()->handle($user, [
        'name' => $vaultName,
    ]);
    $file = UploadedFile::fake()->create($vaultName . '.zip');

    Livewire::actingAs($user)
        ->test(ImportVault::class)
        ->call('open')
        ->set('file', $file);

    $vaults = $user->vaults()->get();
    expect($vaults->count())->toBe(2);
    expect($vaults->get(0)->name)->toBe($vaultName);
    expect($vaults->get(1)->name)->toBe($vaultName . '-1');
});

it('handles name collisions when importing a vault with a name existing in multiple vaults', function (): void {
    $user = User::factory()->create()->first();
    $vaultName = fake()->words(3, true);
    new CreateVault()->handle($user, [
        'name' => $vaultName,
    ]);
    new CreateVault()->handle($user, [
        'name' => $vaultName,
    ]);
    $file = UploadedFile::fake()->create($vaultName . '.zip');

    Livewire::actingAs($user)
        ->test(ImportVault::class)
        ->call('open')
        ->set('file', $file);

    $vaults = $user->vaults()->get();
    expect($vaults->count())->toBe(3);
    expect($vaults->get(0)->name)->toBe($vaultName);
    expect($vaults->get(1)->name)->toBe($vaultName . '-1');
    expect($vaults->get(2)->name)->toBe($vaultName . '-2');
});

it('imports a zip file with files and folders', function (): void {
    $user = User::factory()->create()->first();
    $zip = new ZipArchive();
    $relativePath = 'public/' . Str::random(16) . '.zip';
    Storage::disk('local')->put($relativePath, '');
    $path = Storage::disk('local')->path($relativePath);
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString(fake()->words(3, true) . '.sh', fake()->paragraph());
    $zip->addEmptyDir('Notes');
    $zip->addFromString('Notes/' . fake()->words(3, true) . '.md', fake()->paragraph());
    $zip->close();
    $file = UploadedFile::fake()->createWithContent('vault.zip', file_get_contents($path));

    Livewire::actingAs($user)
        ->test(ImportVault::class)
        ->call('open')
        ->set('file', $file);

    expect($user->vaults()->count())->toBe(1);
});

it('creates links when importing a vault', function (): void {
    $user = User::factory()->create()->first();
    $zip = new ZipArchive();
    $relativePath = 'public/' . Str::random(16) . '.zip';
    Storage::disk('local')->put($relativePath, '');
    $path = Storage::disk('local')->path($relativePath);
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $firstNodeName = fake()->words(3, true);
    $secondNodeName = fake()->words(3, true);
    $zip->addFromString($firstNodeName . '.md', '[link](/' . $secondNodeName . '.md)');
    $zip->addFromString($secondNodeName . '.md', '[link](/' . $firstNodeName . '.md)');
    $zip->close();
    $file = UploadedFile::fake()->createWithContent('vault.zip', file_get_contents($path));

    Livewire::actingAs($user)
        ->test(ImportVault::class)
        ->call('open')
        ->set('file', $file);

    expect($user->vaults()->count())->toBe(1)
        ->and($user->vaults()->first()->nodes()->count())->toBe(2)
        ->and($user->vaults()->first()->nodes()->get()->get(0)->links()->count())->toBe(1)
        ->and($user->vaults()->first()->nodes()->get()->get(1)->links()->count())->toBe(1);
});
