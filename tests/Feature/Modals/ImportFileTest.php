<?php

declare(strict_types=1);

use App\Actions\CreateVault;
use App\Actions\CreateVaultNode;
use App\Livewire\Modals\ImportFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;

it('opens the modal', function (): void {
    $user = User::factory()->create()->first();
    $vault = new CreateVault()->handle($user, [
        'name' => fake()->words(3, true),
    ]);

    Livewire::actingAs($user)
        ->test(ImportFile::class, ['vault' => $vault])
        ->assertSet('show', false)
        ->call('open')
        ->assertSet('show', true);
});

it('opens the modal passing a file as parent node', function (): void {
    $user = User::factory()->create()->first();
    $vault = new CreateVault()->handle($user, [
        'name' => fake()->words(3, true),
    ]);
    $node = new CreateVaultNode()->handle($vault, [
        'is_file' => true,
        'name' => fake()->words(3, true),
        'extension' => 'md',
        'content' => fake()->paragraph(),
    ]);

    Livewire::actingAs($user)
        ->test(ImportFile::class, ['vault' => $vault])
        ->call('open', $node)
        ->assertStatus(400);
});

it('imports a file', function (): void {
    $user = User::factory()->create()->first();
    $vault = new CreateVault()->handle($user, [
        'name' => fake()->words(3, true),
    ]);
    $node = new CreateVaultNode()->handle($vault, [
        'is_file' => false,
        'name' => fake()->words(3, true),
    ]);
    $file = UploadedFile::fake()->create('note.md');

    Livewire::actingAs($user)
        ->test(ImportFile::class, ['vault' => $vault])
        ->assertSet('show', false)
        ->call('open', $node)
        ->set('file', $file)
        ->assertSet('show', false);
});

it('handles name collisions when importing a file with an existing name', function (): void {
    $user = User::factory()->create()->first();
    $vault = new CreateVault()->handle($user, [
        'name' => fake()->words(3, true),
    ]);
    $folderNode = new CreateVaultNode()->handle($vault, [
        'is_file' => false,
        'name' => fake()->words(3, true),
    ]);
    $node = new CreateVaultNode()->handle($vault, [
        'is_file' => true,
        'parent_id' => $folderNode->id,
        'name' => fake()->words(3, true),
        'extension' => 'md',
        'content' => fake()->paragraph(),
    ]);
    $file = UploadedFile::fake()->create($node->name . '.md');

    Livewire::actingAs($user)
        ->test(ImportFile::class, ['vault' => $vault])
        ->call('open', $folderNode)
        ->set('file', $file);

    $nodes = $vault->nodes()->get();
    expect($nodes->count())->toBe(3);
    expect($nodes->get(1)->name)->toBe($node->name);
    expect($nodes->get(2)->name)->toBe($node->name . '-1');
});

it('handles name collisions when importing a file with a name existing in multiple files', function (): void {
    $user = User::factory()->create()->first();
    $vault = new CreateVault()->handle($user, [
        'name' => fake()->words(3, true),
    ]);
    $nodeName = fake()->words(3, true);
    $firstNode = new CreateVaultNode()->handle($vault, [
        'is_file' => true,
        'name' => $nodeName,
        'extension' => 'md',
        'content' => fake()->paragraph(),
    ]);
    $secondNode = new CreateVaultNode()->handle($vault, [
        'is_file' => true,
        'name' => $nodeName . '-1',
        'extension' => 'md',
        'content' => fake()->paragraph(),
    ]);
    $file = UploadedFile::fake()->create($nodeName . '.md');

    Livewire::actingAs($user)
        ->test(ImportFile::class, ['vault' => $vault])
        ->call('open')
        ->set('file', $file);

    $nodes = $vault->nodes()->get();
    expect($nodes->count())->toBe(3);
    expect($nodes->get(0)->name)->toBe($nodeName);
    expect($nodes->get(1)->name)->toBe($nodeName . '-1');
    expect($nodes->get(2)->name)->toBe($nodeName . '-2');
});

it('does not import a file with a non-allowed extension', function (): void {
    $user = User::factory()->create()->first();
    $vault = new CreateVault()->handle($user, [
        'name' => fake()->words(3, true),
    ]);
    $file = UploadedFile::fake()->create('note.sh');

    Livewire::actingAs($user)
        ->test(ImportFile::class, ['vault' => $vault])
        ->assertSet('show', false)
        ->call('open')
        ->set('file', $file)
        ->assertHasErrors('file');
});

it('creates links when importing a file', function (): void {
    $user = User::factory()->create()->first();
    $vault = new CreateVault()->handle($user, [
        'name' => fake()->words(3, true),
    ]);
    $nodeName = fake()->words(3, true);
    $node = new CreateVaultNode()->handle($vault, [
        'is_file' => true,
        'name' => $nodeName,
        'extension' => 'md',
        'content' => fake()->paragraph(),
    ]);
    $file = UploadedFile::fake()->createWithContent('note.md', '[link](/' . $nodeName . '.md)');

    Livewire::actingAs($user)
        ->test(ImportFile::class, ['vault' => $vault])
        ->call('open')
        ->set('file', $file);

    expect($vault->nodes()->get()->get(1)->links()->count())->toBe(1);
});
