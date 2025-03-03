<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TranscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TranscriptionController extends Controller
{
    public function __construct(
        private TranscriptionService $transcriptionService,
    ) {
    }

    /**
     * Process audio from file upload for transcription.
     */
    public function transcribeFile(Request $request): JsonResponse
    {
        $request->validate([
            'audio_file' => 'required|file|mimes:mp3,wav,webm,m4a,ogg,flac|max:10240',
        ]);

        $file = $request->file('audio_file');
        $transcription = $this->transcriptionService->transcribeFile($file);

        return response()->json(['transcription' => $transcription]);
    }

    /**
     * Process base64 encoded audio for transcription.
     */
    public function transcribeBase64(Request $request): JsonResponse
    {
        $request->validate([
            'audio_data' => 'required|string',
        ]);

        $transcription = $this->transcriptionService->transcribeBase64($request->input('audio_data'));

        return response()->json(['transcription' => $transcription]);
    }
}