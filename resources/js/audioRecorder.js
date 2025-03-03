/**
 * Alpine.js component to handle audio recording with system audio support and real-time transcription.
 * 
 * @param {Object} wire - The Livewire component that will receive the audio data
 * @returns {Object} - Alpine.js component data
 */
window.audioRecorder = function(wire) {
    return {
        mediaRecorder: null,
        audioChunks: [],
        isRecording: false,
        audioURL: null,
        recordingStartTime: null,
        recordingTime: '00:00',
        recordingTimer: null,
        captureSystemAudio: false,
        transcriber: null,
        isTranscriberReady: false,
        currentTranscription: '',
        processingAudio: false,
        accumulatedChunks: [],
        transcriptionInterval: null,

        /**
         * Initialize the component
         */
        async init() {
            console.log('🎤 Initializing audio recorder component...');
            try {
                if (this.transcriber) {
                    console.log('⚠️ Transcriber already exists, cleaning up...');
                    this.transcriber = null;
                }

                // Check WebGPU support
                const isWebGPUSupported = await WhisperTranscriber.isSupported();
                console.log('🖥️ WebGPU support status:', isWebGPUSupported);
                if (!isWebGPUSupported) {
                    console.warn('⚠️ WebGPU is not supported. Real-time transcription will not be available.');
                    return;
                }

                // Initialize Whisper
                console.log('🎯 Creating Whisper transcriber instance...');
                this.transcriber = new WhisperTranscriber();
                
                // Set up callbacks before initialization
                this.transcriber.onResult((text) => {
                    console.log('📝 Received transcription result:', text);
                    if (text && typeof text === 'string') {
                        console.log('✍️ Updating transcription display and wire state');
                        this.currentTranscription = text;
                        if (wire && typeof wire.set === 'function') {
                            wire.set('transcription', text);
                        } else {
                            console.error('❌ Wire or wire.set is not available');
                        }
                    }
                });

                this.transcriber.onReady(() => {
                    console.log('✅ Whisper transcriber is ready');
                    this.isTranscriberReady = true;
                });

                console.log('🚀 Starting transcriber initialization...');
                await this.transcriber.initialize();
                console.log('✨ Audio recorder component initialization complete');
            } catch (error) {
                console.error('❌ Error initializing transcriber:', error);
                this.transcriber = null;
                this.isTranscriberReady = false;
            }
        },

        /**
         * Start recording audio
         */
        async startRecording() {
            console.log('🎬 Starting recording process...');
            if (!this.isTranscriberReady) {
                console.error('❌ Cannot start recording - transcriber not ready');
                alert('Transcription system is not ready. Please try again in a moment.');
                return;
            }
            
            console.log('📊 Current state - isTranscriberReady:', this.isTranscriberReady);
            this.audioChunks = [];
            this.accumulatedChunks = [];
            this.audioURL = null;
            this.currentTranscription = '';
            
            try {
                let audioStream;
                
                if (this.captureSystemAudio) {
                    console.log('🎙️ Requesting system audio + microphone access...');
                    // Request both microphone and system audio (display media) access
                    const displayStream = await navigator.mediaDevices.getDisplayMedia({
                        video: false,
                        audio: true
                    });
                    console.log('🖥️ System audio stream acquired');
                    
                    // Get microphone access
                    const micStream = await navigator.mediaDevices.getUserMedia({
                        audio: true,
                        video: false
                    });
                    console.log('🎤 Microphone stream acquired');
                    
                    // Combine the audio tracks from both streams
                    const audioTracks = [
                        ...displayStream.getAudioTracks(),
                        ...micStream.getAudioTracks()
                    ];
                    console.log('🔄 Combined audio tracks:', audioTracks.length);
                    
                    // Create a new stream with all audio tracks
                    audioStream = new MediaStream(audioTracks);
                } else {
                    console.log('🎤 Requesting microphone-only access...');
                    // Just get microphone access
                    audioStream = await navigator.mediaDevices.getUserMedia({
                        audio: true,
                        video: false
                    });
                    console.log('✅ Microphone stream acquired');
                }
                
                // Initialize MediaRecorder with the stream
                console.log('📼 Creating MediaRecorder...');
                this.mediaRecorder = new MediaRecorder(audioStream, {
                    mimeType: 'audio/webm;codecs=opus'
                });
                console.log('✅ MediaRecorder created with settings:', this.mediaRecorder.mimeType);
                
                // Set up event handlers
                this.mediaRecorder.ondataavailable = (event) => {
                    if (event.data.size > 0) {
                        console.log('📦 Received audio chunk, size:', event.data.size);
                        this.audioChunks.push(event.data);
                        this.accumulatedChunks.push(event.data);
                    }
                };
                
                this.mediaRecorder.onstop = async () => {
                    console.log('⏹️ Recording stopped, processing final audio...');
                    try {
                        const audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                        console.log('📦 Final audio blob size:', audioBlob.size);
                        this.audioURL = URL.createObjectURL(audioBlob);
                        
                        // Clean up tracks
                        if (this.cleanupTracks) {
                            console.log('🧹 Cleaning up audio tracks...');
                            this.cleanupTracks();
                        }
                        
                        // Stop the timer and transcription interval
                        clearInterval(this.recordingTimer);
                        clearInterval(this.transcriptionInterval);
                        this.recordingStartTime = null;

                        // Process the complete audio for final transcription
                        if (this.isTranscriberReady && this.transcriber) {
                            console.log('🎯 Processing final transcription...');
                            const transcription = await this.transcriber.transcribe(audioBlob);
                            if (transcription && typeof transcription === 'string') {
                                console.log('📝 Final transcription result:', transcription);
                                this.currentTranscription = transcription;
                                wire.set('transcription', transcription);
                            }
                        } else {
                            console.warn('⚠️ Cannot process final transcription - transcriber not ready');
                        }
                    } catch (error) {
                        console.error('❌ Error in onstop handler:', error);
                        this.errorMessage = 'Error processing audio: ' + error.message;
                    }
                };
                
                // Start recording with chunks every 3 seconds
                console.log('▶️ Starting MediaRecorder...');
                this.mediaRecorder.start(3000);
                this.isRecording = true;
                console.log('✅ Recording started');
                
                // Start the timer
                this.recordingStartTime = Date.now();
                this.recordingTimer = setInterval(() => this.updateRecordingTime(), 1000);

                // Set up periodic transcription of accumulated chunks
                if (this.isTranscriberReady && this.transcriber) {
                    console.log('⚡ Setting up periodic transcription...');
                    this.transcriptionInterval = setInterval(async () => {
                        if (this.accumulatedChunks.length > 0 && !this.processingAudio) {
                            console.log('🔄 Processing accumulated chunks:', this.accumulatedChunks.length);
                            this.processingAudio = true;
                            try {
                                const audioBlob = new Blob(this.accumulatedChunks, { type: 'audio/webm' });
                                console.log('📦 Created audio blob from chunks, size:', audioBlob.size);
                                const transcription = await this.transcriber.transcribe(audioBlob);
                                if (transcription && typeof transcription === 'string') {
                                    console.log('📝 Interim transcription result:', transcription);
                                    this.currentTranscription = transcription;
                                    wire.set('transcription', transcription);
                                }
                                // Clear accumulated chunks after successful transcription
                                this.accumulatedChunks = [];
                            } catch (error) {
                                console.error('❌ Error transcribing audio chunk:', error);
                            } finally {
                                this.processingAudio = false;
                            }
                        }
                    }, 5000); // Try to transcribe every 5 seconds
                } else {
                    console.warn('⚠️ Periodic transcription not set up - transcriber not ready');
                }
                
            } catch (error) {
                console.error('❌ Error starting recording:', error);
                alert('Could not start recording: ' + error.message);
            }
        },

        /**
         * Stop recording
         */
        stopRecording() {
            console.log('⏹️ Stopping recording...');
            if (!this.mediaRecorder) {
                console.warn('⚠️ No active MediaRecorder found');
                return;
            }
            if (!this.isRecording) {
                console.warn('⚠️ Not currently recording');
                return;
            }
            try {
                this.mediaRecorder.stop();
                this.isRecording = false;
                console.log('✅ Recording stopped');
            } catch (error) {
                console.error('❌ Error stopping recording:', error);
                // Try to clean up anyway
                this.isRecording = false;
                this.mediaRecorder = null;
            }
        },

        /**
         * Reset recording state
         */
        resetRecording() {
            console.log('🔄 Resetting recording state...');
            this.audioChunks = [];
            this.accumulatedChunks = [];
            this.audioURL = null;
            this.currentTranscription = '';
            if (this.transcriptionInterval) {
                clearInterval(this.transcriptionInterval);
            }
            console.log('✅ Recording state reset');
        },

        /**
         * Update recording time display
         */
        updateRecordingTime() {
            if (!this.recordingStartTime) return;
            
            const elapsed = Math.floor((Date.now() - this.recordingStartTime) / 1000);
            const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
            const seconds = (elapsed % 60).toString().padStart(2, '0');
            this.recordingTime = `${minutes}:${seconds}`;
        }
    };
}; 