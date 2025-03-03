import { pipeline } from '@xenova/transformers';

/**
 * Class to handle real-time transcription using WebGPU Whisper
 */
class WhisperTranscriber {
    constructor() {
        console.log('🎯 Creating new WhisperTranscriber instance');
        this.model = 'Xenova/whisper-tiny';
        this.pipeline = null;
        this.isInitialized = false;
        this.resultCallback = null;
        this.readyCallback = null;
        this.isTranscribing = false;
        this.language = 'en';
        this.task = 'transcribe';
        this.chunkLength = 30;
        this.stride = 5;
        this.returnTimestamps = false;
        this.supportedLanguages = new Set(['en']);
    }

    /**
     * Initialize the transcriber
     */
    async initialize() {
        console.log('🚀 Initializing WhisperTranscriber...');
        if (this.isInitialized) {
            console.log('⚠️ WhisperTranscriber already initialized');
            return;
        }
        try {
            const { pipeline } = await import('@xenova/transformers');
            console.log('📦 Transformers package imported');

            console.log('⚡ Loading Whisper model:', this.model);
            this.pipeline = await pipeline('automatic-speech-recognition', this.model, {
                progress_callback: (progress) => {
                    console.log('📥 Raw progress data:', progress);
                    const percentage = progress && progress.progress ? Math.round(progress.progress * 100) : 0;
                    console.log('📥 Model loading progress:', percentage + '%');
                    if (progress.status) {
                        console.log('📥 Status:', progress.status);
                    }
                }
            });

            // Try to get supported languages from the model
            try {
                const modelConfig = await this.pipeline.processor?.tokenizer?.model?.config();
                if (modelConfig?.lang2id) {
                    this.supportedLanguages = new Set(Object.keys(modelConfig.lang2id));
                    console.log('📚 Supported languages:', Array.from(this.supportedLanguages));
                }
            } catch (error) {
                console.warn('⚠️ Could not get supported languages:', error);
            }

            console.log('✨ WhisperTranscriber initialization complete');
            this.isInitialized = true;
            if (this.readyCallback) {
                this.readyCallback();
            }
        } catch (error) {
            console.error('❌ Error initializing WhisperTranscriber:', error);
            throw error;
        }
    }

    /**
     * Set callback for when transcription results are available
     */
    onResult(callback) {
        console.log('📝 Setting up result callback');
        this.resultCallback = callback;
    }

    /**
     * Set callback for when the transcriber is ready
     */
    onReady(callback) {
        console.log('🔄 Setting up ready callback');
        this.readyCallback = callback;
        if (this.isInitialized) {
            callback();
        }
    }

    /**
     * Set the language for transcription
     */
    setLanguage(language) {
        if (!this.supportedLanguages.has(language)) {
            console.warn(`⚠️ Language "${language}" not supported, using English`);
            this.language = 'en';
        } else {
            this.language = language;
        }
    }

    /**
     * Set the task (transcribe or translate)
     */
    setTask(task) {
        if (task !== 'transcribe' && task !== 'translate') {
            console.warn(`⚠️ Task "${task}" not supported, using transcribe`);
            this.task = 'transcribe';
        } else {
            this.task = task;
        }
    }

    /**
     * Resample audio data to target sample rate
     */
    async resampleAudio(audioBuffer, targetSampleRate) {
        console.log('🎵 Resampling audio from', audioBuffer.sampleRate, 'Hz to', targetSampleRate, 'Hz');
        
        const offlineCtx = new OfflineAudioContext(
            1, // mono
            Math.ceil(audioBuffer.duration * targetSampleRate),
            targetSampleRate
        );

        // Create buffer source
        const source = offlineCtx.createBufferSource();
        source.buffer = audioBuffer;
        source.connect(offlineCtx.destination);
        source.start();

        // Render the resampled buffer
        const renderedBuffer = await offlineCtx.startRendering();
        console.log('✅ Audio resampled:', {
            originalDuration: audioBuffer.duration,
            newDuration: renderedBuffer.duration,
            newSampleRate: renderedBuffer.sampleRate
        });

        return renderedBuffer;
    }

    /**
     * Convert audio blob to PCM audio data
     */
    async blobToAudioData(blob) {
        console.log('🔄 Converting audio blob to PCM format...');
        try {
            // Create an audio context
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            console.log('🎵 Created AudioContext, sample rate:', audioContext.sampleRate);

            // Convert blob to array buffer
            const arrayBuffer = await blob.arrayBuffer();
            console.log('✅ Audio blob converted to ArrayBuffer, size:', arrayBuffer.byteLength);

            // Decode the audio data
            console.log('🎼 Decoding audio data...');
            const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);
            console.log('✅ Audio decoded:', {
                duration: audioBuffer.duration,
                numberOfChannels: audioBuffer.numberOfChannels,
                sampleRate: audioBuffer.sampleRate
            });

            // Resample to 16kHz if needed
            const targetSampleRate = 16000;
            const resampledBuffer = audioBuffer.sampleRate !== targetSampleRate 
                ? await this.resampleAudio(audioBuffer, targetSampleRate)
                : audioBuffer;

            // Get the PCM data from the first channel
            const pcmData = resampledBuffer.getChannelData(0);
            
            // Calculate RMS to check audio levels
            let rms = 0;
            for (let i = 0; i < pcmData.length; i++) {
                rms += pcmData[i] * pcmData[i];
            }
            rms = Math.sqrt(rms / pcmData.length);
            
            // Calculate appropriate gain
            const targetRms = 0.2;
            const gain = rms < targetRms ? targetRms / (rms || 1) : 1;
            
            console.log('📊 PCM data details:', {
                length: pcmData.length,
                sampleRate: resampledBuffer.sampleRate,
                min: Math.min(...pcmData),
                max: Math.max(...pcmData),
                rms: rms,
                appliedGain: gain,
                hasAudio: pcmData.some(x => Math.abs(x) > 0.01)
            });

            // Create normalized Float32Array for Whisper
            const normalizedData = new Float32Array(pcmData.length);
            for (let i = 0; i < pcmData.length; i++) {
                // Apply gain and normalize to [-1, 1]
                normalizedData[i] = Math.max(-1, Math.min(1, pcmData[i] * gain));
            }

            return normalizedData;
        } catch (error) {
            console.error('❌ Error converting audio to PCM:', error);
            throw new Error('Failed to convert audio to PCM: ' + error.message);
        }
    }

    /**
     * Transcribe audio data
     */
    async transcribe(audioData) {
        console.log('🎯 Starting transcription...');
        if (!this.isInitialized) {
            console.error('❌ Cannot transcribe - WhisperTranscriber not initialized');
            throw new Error('WhisperTranscriber is not initialized');
        }

        if (this.isTranscribing) {
            console.warn('⚠️ Transcription already in progress');
            return null;
        }

        try {
            this.isTranscribing = true;
            console.log('🔍 Processing audio data...');

            // Convert blob to PCM audio data if needed
            let inputArray;
            if (audioData instanceof Blob) {
                console.log('🔄 Converting Blob to PCM...', {
                    type: audioData.type,
                    size: audioData.size
                });
                inputArray = await this.blobToAudioData(audioData);
            } else {
                inputArray = audioData;
            }

            // Log the audio data details
            console.log('📊 Audio data details:', {
                length: inputArray.length,
                type: inputArray.constructor.name,
                min: Math.min(...inputArray),
                max: Math.max(...inputArray)
            });

            const config = {
                chunk_length_s: this.chunkLength,
                stride_length_s: this.stride,
                language: this.language,
                task: this.task,
                return_timestamps: this.returnTimestamps,
                sampling_rate: 16000
            };

            console.log('🎯 Running transcription with config:', config);
            console.log('🔍 Model status:', {
                isInitialized: this.isInitialized,
                pipeline: !!this.pipeline,
                model: this.model
            });

            // Pass the Float32Array directly to the pipeline
            const result = await this.pipeline(inputArray, config);

            console.log('✅ Raw transcription result:', result);
            
            // Enhanced result validation
            if (!result) {
                console.warn('⚠️ Transcription returned null result');
                return { text: '' };
            }

            if (!result.text && result.text !== '') {
                console.warn('⚠️ Transcription result missing text property');
                return { text: '' };
            }

            if (result.text === '') {
                console.warn('⚠️ Transcription result is empty string - possible causes:');
                console.warn('   - Audio may be too quiet');
                console.warn('   - No speech detected');
                console.warn('   - Audio format may be incompatible');
            }

            if (this.resultCallback) {
                this.resultCallback(result.text);
            }

            return result;
        } catch (error) {
            console.error('❌ Error during transcription:', error);
            console.error('Error details:', {
                name: error.name,
                message: error.message,
                stack: error.stack
            });
            throw error;
        } finally {
            this.isTranscribing = false;
        }
    }

    /**
     * Check if WebGPU is supported
     */
    static async isSupported() {
        console.log('🔍 Checking WebGPU support...');
        try {
            if (!navigator.gpu) {
                console.warn('⚠️ WebGPU is not available - navigator.gpu is undefined');
                return false;
            }
            const adapter = await navigator.gpu.requestAdapter();
            if (!adapter) {
                console.warn('⚠️ WebGPU is not available - no adapter found');
                return false;
            }
            console.log('✅ WebGPU is supported');
            return true;
        } catch (error) {
            console.error('❌ Error checking WebGPU support:', error);
            return false;
        }
    }
}

// Export as global for Alpine.js
window.WhisperTranscriber = WhisperTranscriber; 