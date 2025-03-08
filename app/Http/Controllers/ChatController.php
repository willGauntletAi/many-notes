<?php

namespace App\Http\Controllers;

use App\Models\Vault;
use App\Models\VaultChat;
use App\Models\ChatMessage;
use App\Services\RAGService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class ChatController
{
    protected $ragService;

    public function __construct(RAGService $ragService)
    {
        $this->ragService = $ragService;
    }

    /**
     * Display a listing of chats for a vault.
     */
    public function index(Request $request, Vault $vault)
    {
        Gate::authorize('view', $vault);

        $chats = $vault->chats()->orderBy('created_at', 'desc')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'chats' => $chats
            ]);
        }

        return view('chat.index', [
            'vault' => $vault,
            'chats' => $chats
        ]);
    }

    /**
     * Store a newly created chat.
     */
    public function store(Request $request, Vault $vault)
    {
        Gate::authorize('update', $vault);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $chat = $vault->chats()->create([
            'name' => $validated['name'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'chat' => $chat
            ], 201);
        }

        return redirect()->route('chat.show', ['vault' => $vault->id, 'chat' => $chat->id])
            ->with('success', 'Chat created successfully.');
    }

    /**
     * Display the specified chat.
     */
    public function show(Request $request, Vault $vault, VaultChat $chat)
    {
        Gate::authorize('view', $vault);

        $messages = $chat->messages()->orderBy('created_at', 'asc')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'chat' => $chat,
                'messages' => $messages
            ]);
        }

        return view('chat.show', [
            'vault' => $vault,
            'chat' => $chat,
            'messages' => $messages
        ]);
    }

    /**
     * Update the specified chat.
     */
    public function update(Request $request, Vault $vault, VaultChat $chat)
    {
        Gate::authorize('update', $vault);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $chat->update([
            'name' => $validated['name'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'chat' => $chat
            ]);
        }

        return redirect()->route('chat.show', ['vault' => $vault->id, 'chat' => $chat->id])
            ->with('success', 'Chat updated successfully.');
    }

    /**
     * Remove the specified chat.
     */
    public function destroy(Request $request, Vault $vault, VaultChat $chat)
    {
        Gate::authorize('update', $vault);

        $chat->delete();

        if ($request->wantsJson()) {
            return response()->json([], 204);
        }

        return redirect()->route('chat.index', ['vault' => $vault->id])
            ->with('success', 'Chat deleted successfully.');
    }

    /**
     * Send a message to the chat.
     */
    public function sendMessage(Request $request, Vault $vault, VaultChat $chat)
    {
        Gate::authorize('view', $vault);

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        // Create user message
        $userMessage = $chat->messages()->create([
            'role' => 'user',
            'content' => $validated['content'],
        ]);

        try {
            // Process the message with RAG
            $response = $this->ragService->processQuery($vault, $validated['content']);

            // Create assistant message
            $assistantMessage = $chat->messages()->create([
                'role' => 'assistant',
                'content' => $response,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'user_message' => $userMessage,
                    'assistant_message' => $assistantMessage
                ]);
            }

            return redirect()->route('chat.show', ['vault' => $vault->id, 'chat' => $chat->id]);
        } catch (\Exception $e) {
            Log::error('Error processing chat message: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'Failed to process message: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('chat.show', ['vault' => $vault->id, 'chat' => $chat->id])
                ->with('error', 'Failed to process message: ' . $e->getMessage());
        }
    }

    /**
     * Generate embeddings for a vault's notes
     */
    public function generateEmbeddings(Request $request, Vault $vault)
    {
        // Authorize access to the vault
        Gate::authorize('view', $vault);
        
        try {
            // Clear any existing job in the queue for this vault
            DB::table('jobs')
                ->where('queue', 'embeddings')
                ->where('payload', 'like', '%"vault_id":' . $vault->id . '%')
                ->delete();
            
            // Create the job manually
            $job = new \App\Jobs\GenerateEmbeddingsJob($vault);
            
            // Dispatch the job with specific options to force queueing
            dispatch($job)
                ->onQueue('embeddings')
                ->afterCommit() // Only dispatch after the current database transaction commits
                ->delay(now()->addSeconds(5)); // Add a small delay to ensure it's processed asynchronously
            
            // Log that we've queued the job
            Log::info('Embedding generation job dispatched for vault: ' . $vault->id);
            
            // Check if the job was queued
            $pendingJobs = DB::table('jobs')->where('queue', 'embeddings')->count();
            Log::info('Pending embedding jobs in queue: ' . $pendingJobs);
            
            return response()->json([
                'success' => true,
                'message' => 'Notes indexing started in the background. This process may take several minutes to complete.',
                'pending_jobs' => $pendingJobs
            ]);
        } catch (\Exception $e) {
            Log::error('Error queueing embedding generation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while starting the indexing process: ' . $e->getMessage()
            ], 500);
        }
    }
}
