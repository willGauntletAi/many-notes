<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class TranscriptionService
{
    /**
     * Transcribe audio from an uploaded file.
     */
    public function transcribeFile(UploadedFile $file): string
    {
        $path = $file->store('transcription', 'local');
        
        try {
            // In a real implementation, you would call a transcription API here.
            // For demonstration purposes, we're returning dummy text
            $transcription = $this->generateDummyTranscription();
            
            // Clean up the temporary file
            Storage::disk('local')->delete($path);
            
            return $transcription;
        } catch (\Exception $e) {
            Log::error('Transcription error: ' . $e->getMessage());
            Storage::disk('local')->delete($path);
            return 'Error transcribing audio: ' . $e->getMessage();
        }
    }
    
    /**
     * Transcribe audio from base64 encoded data.
     */
    public function transcribeBase64(string $base64Audio): string
    {
        try {
            // Strip the data URI prefix if present
            $base64Audio = preg_replace('/^data:audio\/\w+;base64,/', '', $base64Audio);
            
            // Decode the base64 data
            $audioData = base64_decode($base64Audio);
            
            if ($audioData === false) {
                throw new \Exception('Invalid base64 data');
            }
            
            // Save to a temporary file
            $filename = 'recording-' . Str::uuid() . '.webm';
            $path = 'transcription/' . $filename;
            Storage::disk('local')->put($path, $audioData);
            
            // In a real implementation, you would call a transcription API here
            $transcription = $this->generateDummyTranscription();
            
            // Clean up the temporary file
            Storage::disk('local')->delete($path);
            
            return $transcription;
        } catch (\Exception $e) {
            Log::error('Transcription error: ' . $e->getMessage());
            return 'Error transcribing audio: ' . $e->getMessage();
        }
    }
    
    /**
     * Generate dummy transcription text for demonstration purposes.
     */
    private function generateDummyTranscription(): string
    {
        $phrases = [
            'This is a test of the audio transcription feature.',
            'Many Notes allows you to take notes and organize your thoughts.',
            'The audio transcription feature lets you capture your ideas quickly.',
            'You can record both from your microphone and system audio.',
            'This transcription will be inserted into your currently open note.',
        ];
        
        return $phrases[array_rand($phrases)];
    }
}