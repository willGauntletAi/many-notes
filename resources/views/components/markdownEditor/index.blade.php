<div class="flex flex-col h-full gap-3 overflow-hidden" 
    x-data="toolbar"
    x-init="initEditor()"
    @mde-link.window="link($event.detail.name, $event.detail.path); $nextTick(() => { editor.focus() });"
    @mde-image.window="image($event.detail.name, $event.detail.path); $nextTick(() => { editor.focus() });"
    @insert-transcription.window="insertTranscription($event.detail.transcription); $nextTick(() => { editor.focus() });"
    {{ $attributes }}
>
    <x-markdownEditor.toolbar x-show="!isSmallDevice()" x-cloak />
    <textarea class="w-full h-full p-0 px-1 bg-transparent border-0 focus:ring-0 focus:outline-0"
        id="noteEdit" x-show="isEditMode" wire:model.live.debounce.500ms="nodeForm.content"
        @keyup.enter="newLine"
    ></textarea>
    <div class="pr-1 overflow-y-auto markdown-body" id="noteView" x-show="!isEditMode" x-html="html"></div>
    <x-markdownEditor.toolbar x-show="isSmallDevice()" x-cloak />
</div>

@script
    <script>
        Alpine.data('toolbar', () => ({
            editor: null,
            mediaRecorder: null,
            isRecording: false,
            recordingSource: '',
            audioChunks: [],
            
            getSelection() {
                return this.editor.value.substring(this.editor.selectionStart, this.editor.selectionEnd);
            },
            
            // Audio recording functions
            startMicRecording() {
                if (this.isRecording) {
                    this.stopRecording();
                    return;
                }
                
                this.recordingSource = 'Microphone';
                this.startRecording({ audio: true });
            },
            
            startSystemRecording() {
                if (this.isRecording) {
                    this.stopRecording();
                    return;
                }
                
                this.recordingSource = 'System Audio';
                
                // Request system audio with getDisplayMedia
                navigator.mediaDevices.getDisplayMedia({ 
                    audio: true, 
                    video: true // Video is required for Chrome, we'll discard it later
                })
                .then(stream => {
                    // Keep only the audio tracks
                    const audioTracks = stream.getAudioTracks();
                    if (audioTracks.length === 0) {
                        alert('No audio track available. Please ensure you selected "Share audio" in the dialog.');
                        stream.getTracks().forEach(track => track.stop());
                        return;
                    }
                    
                    // Stop video tracks if any
                    stream.getVideoTracks().forEach(track => track.stop());
                    
                    // Create a new MediaStream with only audio
                    const audioStream = new MediaStream(audioTracks);
                    this.setupMediaRecorder(audioStream);
                })
                .catch(error => {
                    console.error('Error accessing system audio:', error);
                    alert('Could not access system audio: ' + error.message);
                });
            },
            
            startRecording(constraints) {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    alert('Audio recording is not supported in this browser');
                    return;
                }
                
                navigator.mediaDevices.getUserMedia(constraints)
                    .then(stream => {
                        this.setupMediaRecorder(stream);
                    })
                    .catch(error => {
                        console.error('Error accessing media devices:', error);
                        alert('Could not access microphone: ' + error.message);
                    });
            },
            
            setupMediaRecorder(stream) {
                this.audioChunks = [];
                this.mediaRecorder = new MediaRecorder(stream);
                
                this.mediaRecorder.ondataavailable = (event) => {
                    if (event.data.size > 0) {
                        this.audioChunks.push(event.data);
                    }
                };
                
                this.mediaRecorder.onstop = () => {
                    // Stop all tracks in the stream
                    stream.getTracks().forEach(track => track.stop());
                    
                    // Process the recording
                    this.processRecording();
                };
                
                // Start recording
                this.mediaRecorder.start();
                this.isRecording = true;
            },
            
            stopRecording() {
                if (this.mediaRecorder && this.isRecording) {
                    this.mediaRecorder.stop();
                    this.isRecording = false;
                }
            },
            
            processRecording() {
                // Create a blob from the audio chunks
                const audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                
                // Convert to base64
                const reader = new FileReader();
                reader.readAsDataURL(audioBlob);
                reader.onloadend = () => {
                    const base64Audio = reader.result;
                    this.transcribeAudio(base64Audio);
                };
            },
            
            initEditor() {
                this.editor = document.getElementById('noteEdit');
            },
            
            insertTranscription(transcription) {
                const { selectionEnd } = this.editor;
                this.setRangeText("\n\n" + transcription + "\n\n", selectionEnd, selectionEnd);
            },
            
            async transcribeAudio(base64Audio) {
                // Show a loading indicator
                const { selectionStart, selectionEnd } = this.editor;
                const loadingText = "\n\n[Transcribing audio...]\n\n";
                this.setRangeText(loadingText, selectionEnd, selectionEnd);
                const loadingPosition = selectionEnd;

                try {
                    // Convert base64 back to blob
                    const byteString = atob(base64Audio.split(',')[1]);
                    const mimeString = base64Audio.split(',')[0].split(':')[1].split(';')[0];
                    const ab = new ArrayBuffer(byteString.length);
                    const ia = new Uint8Array(ab);
                    for (let i = 0; i < byteString.length; i++) {
                        ia[i] = byteString.charCodeAt(i);
                    }
                    const audioBlob = new Blob([ab], { type: mimeString });

                    // Validate audio data
                    if (audioBlob.size === 0) {
                        throw new Error('No audio data received');
                    }
                    console.log('📊 Audio blob details - Type:', mimeString, 'Size:', audioBlob.size, 'bytes');

                    // Initialize transcriber if needed
                    if (!this.transcriber || !this.transcriber.isInitialized) {
                        if (!window.WhisperTranscriber) {
                            throw new Error('Transcriber not available');
                        }
                        console.log('🎯 Creating new transcriber instance...');
                        this.transcriber = new WhisperTranscriber();
                        await this.transcriber.initialize();
                    }

                    // Transcribe the audio
                    console.log('🎯 Starting transcription...');
                    const result = await this.transcriber.transcribe(audioBlob);
                    console.log('📝 Raw transcription result:', result);
                    
                    // Handle empty or invalid results
                    if (!result || (typeof result === 'object' && (!result.text || result.text.trim() === ''))) {
                        console.warn('⚠️ Empty transcription result received');
                        this.setRangeText(
                            "\n\n[No speech detected in the audio. Please try recording again with clearer audio.]\n\n",
                            loadingPosition,
                            loadingPosition + loadingText.length
                        );
                        return;
                    }

                    // Extract text from result (handles both string and object returns)
                    const transcription = typeof result === 'string' ? result : result.text;
                    console.log('✅ Transcription complete:', transcription);
                    
                    this.setRangeText(
                        "\n\n" + transcription + "\n\n",
                        loadingPosition,
                        loadingPosition + loadingText.length
                    );
                } catch (error) {
                    console.error('❌ Error transcribing audio:', error);
                    this.setRangeText(
                        "\n\n[Transcription failed: " + error.message + "]\n\n",
                        loadingPosition,
                        loadingPosition + loadingText.length
                    );
                }
            },

            parseSelection() {
                const selection = this.getSelection();

                if (selection.length === 0) {
                    return;
                }

                // Remove leading whitespaces
                this.editor.selectionStart += selection.length - selection.trimStart().length;

                // Remove trailing whitespaces
                this.editor.selectionEnd -= selection.length - selection.trimEnd().length;
            },

            changeSelection(text, moveSelectionStart, moveSelectionEnd = null) {
                const { selectionStart, selectionEnd } = this.editor;
                moveSelectionEnd = moveSelectionEnd === null
                    ? selectionEnd + moveSelectionStart
                    : selectionStart + moveSelectionEnd;
                this.editor.setRangeText(text);
                this.editor.focus();
                this.editor.setSelectionRange(selectionStart + moveSelectionStart, moveSelectionEnd);
                this.editor.dispatchEvent(new Event('input'));
            },

            setRangeText(replacement, startSelection = null, endSelection = null, selectMode = "preserve") {
                this.editor.setRangeText(replacement, startSelection, endSelection, selectMode);
                this.editor.focus();
                this.editor.dispatchEvent(new Event('input'));
            },

            setSelectionRange(selectionStart, selectionEnd, selectionDirection = "none") {
                this.editor.setSelectionRange(selectionStart, selectionEnd, selectionDirection);
            },

            parseLine(indexStart, indexEnd) {
                const lineStart = this.editor.value.substring(0, indexStart).lastIndexOf("\n") + 1;
                const lineEnd = this.editor.value.substring(indexStart).indexOf("\n") > -1
                    ? this.editor.value.substring(indexStart).indexOf("\n") + indexStart
                    : this.editor.value.length;
                const lineText = this.editor.value.substring(lineStart, lineEnd);
                const selectionStart = indexStart;
                const selectionEnd = indexEnd < lineEnd ? indexEnd : lineEnd;
                const selectionText = this.editor.value.substring(selectionStart, selectionEnd);
                let fullSelectionStart = selectionStart;
                if (selectionText.slice(0, 1) != " ") {
                    fullSelectionStart = this.editor.value.substring(lineStart, selectionStart)
                        .lastIndexOf(" ") + lineStart + 1;
                }
                let fullSelectionEnd = selectionEnd;
                if (selectionText.slice(-1) != " ") {
                    let aux = this.editor.value.substring(selectionEnd, lineEnd).indexOf(" ");
                    fullSelectionEnd = aux < 0 ? lineEnd : selectionEnd + aux;
                }
                const fullSelectionText = this.editor.value.substring(fullSelectionStart, fullSelectionEnd);

                return {
                    'lineStart': lineStart,
                    'lineEnd': lineEnd,
                    'lineText': lineText,
                    'selectionStart': selectionStart,
                    'selectionEnd': selectionEnd,
                    'selectionText': selectionText,
                    'selectionPrefixText': selectionStart - fullSelectionStart > 0
                        ? fullSelectionText.slice(0, selectionStart - fullSelectionStart)
                        : '',
                    'selectionSuffixText': selectionEnd - fullSelectionEnd < 0
                        ? fullSelectionText.slice(selectionEnd - fullSelectionEnd)
                        : '',
                    'fullSelectionStart': fullSelectionStart,
                    'fullSelectionEnd': fullSelectionEnd,
                    'fullSelectionText': fullSelectionText,
                    'originalSelectionStart': indexStart,
                    'originalSelectionEnd': indexEnd,
                };
            },

            parseAllLines(indexStart, indexEnd) {
                const lines = [];

                // Parse selected lines
                let pieces = this.editor.value.substring(indexStart, indexEnd).split(/\r?\n/);
                let indexInc = indexStart;
                for (i in pieces) {
                    lines.push({
                        'lineStart': indexInc,
                        'lineText': pieces[i],
                    });
                    indexInc += pieces[i].length + 1;
                }

                // Parse rest of last selected line
                pieces = this.editor.value.substring(indexEnd).split(/\r?\n/);
                lines[lines.length - 1].lineText += pieces.length
                    ? pieces[0]
                    : this.editor.value.substring(indexEnd);

                // Parse rest of first selected line
                pieces = this.editor.value.substring(0, indexStart).split(/\r?\n/);
                lines[0].lineStart = pieces.length ? indexStart - pieces[pieces.length - 1].length : 0;
                lines[0].lineText = pieces.length
                    ? pieces[pieces.length - 1] + lines[0].lineText
                    : this.editor.value.substring(0, indexEnd) + lines[0].lineText;

                return lines;
            },

            deleteStyles() {
                const parsed = this.parseLine(this.editor.selectionStart, this.editor.selectionEnd);

                if (this.hasTaskList(parsed.lineText)) {
                    this.setRangeText("", parsed.lineStart, parsed.lineStart + 6);
                    this.setSelectionRange(parsed.originalSelectionStart - 6, parsed.originalSelectionEnd - 6);
                    this.deleteStyles();
                    return;
                }

                if (this.hasUnorderedList(parsed.lineText)) {
                    this.setRangeText("", parsed.lineStart, parsed.lineStart + 2);
                    this.setSelectionRange(parsed.originalSelectionStart - 2, parsed.originalSelectionEnd - 2);
                    this.deleteStyles();
                    return;
                }

                if (this.hasOrderedList(parsed.lineText)) {
                    const numberLength = (this.getOrderedList(parsed.lineText)).toString().length;
                    this.setRangeText("", parsed.lineStart, parsed.lineStart + numberLength + 2);
                    this.setSelectionRange(
                        parsed.originalSelectionStart - numberLength - 2,
                        parsed.originalSelectionEnd - numberLength - 2,
                    );
                    this.deleteStyles();
                    return;
                }

                if (this.hasHeading(parsed.lineText)) {
                    const headingLength = this.getHeading(parsed.lineText).length;
                    this.setRangeText("", parsed.lineStart, parsed.lineStart + headingLength);
                    this.setSelectionRange(
                        parsed.originalSelectionStart - headingLength,
                        parsed.originalSelectionEnd - headingLength,
                    );
                    this.deleteStyles();
                    return;
                }

                if (this.hasBlockquote(parsed.lineText)) {
                    this.setRangeText("", parsed.lineStart, parsed.lineStart + 2);
                    this.setSelectionRange(parsed.originalSelectionStart - 2, parsed.originalSelectionEnd - 2);
                    this.deleteStyles();
                    return;
                }
            },

            newLine() {
                const parsed = this.parseLine(this.editor.selectionStart - 1, this.editor.selectionEnd - 1);

                if (this.hasTaskList(parsed.lineText)) {
                    this.addTaskList(this.editor.selectionStart);
                    return;
                }

                if (this.hasUnorderedList(parsed.lineText)) {
                    this.addUnorderedList(this.editor.selectionStart);
                    return;
                }

                if (this.hasOrderedList(parsed.lineText)) {
                    const number = this.getOrderedList(parsed.lineText) + 1;
                    this.addOrderedList(this.editor.selectionStart, number);
                    return;
                }
            },

            unorderedList() {
                const parsed = this.parseAllLines(this.editor.selectionStart, this.editor.selectionEnd);
                const everyLineUnorderedList = parsed.every(element => this.hasUnorderedList(element.lineText));
                let selectionStart, selectionEnd, addedTextLength = 0;

                for (i in parsed) {
                    ({ selectionStart, selectionEnd } = this.editor);

                    if (everyLineUnorderedList) {
                        this.setRangeText(
                            "",
                            parsed[i].lineStart + addedTextLength, parsed[i].lineStart + addedTextLength + 2
                        );
                        this.setSelectionRange(
                            parsed[i].lineStart < selectionStart
                                ? selectionStart - 2
                                : selectionStart, selectionEnd - 2,
                            );
                        addedTextLength -= 2;
                        continue;
                    }

                    if (!this.hasUnorderedList(parsed[i].lineText)) {
                        const text = "- ";
                        this.setRangeText(
                            text,
                            parsed[i].lineStart + addedTextLength,
                            parsed[i].lineStart + addedTextLength,
                        );
                        this.setSelectionRange(
                            parsed[i].lineStart < selectionStart
                                ? selectionStart + text.length
                                : selectionStart, selectionEnd + text.length,
                            );
                        addedTextLength += text.length;
                    }
                }
            },

            hasUnorderedList(text) {
                return !this.hasTaskList(text) && /^- /.test(text);
            },

            addUnorderedList(index) {
                const { selectionStart, selectionEnd } = this.editor;
                const text = "- ";
                this.setRangeText(text, index, index);
                this.setSelectionRange(selectionStart + text.length, selectionEnd + text.length);
            },

            orderedList() {
                const parsed = this.parseAllLines(this.editor.selectionStart, this.editor.selectionEnd);
                const everyLineOrderedList = parsed.every(element => this.hasOrderedList(element.lineText));
                let selectionStart, selectionEnd, numberLength, addedTextLength = 0,
                    number = 1;

                // Find if previous line has number list and get number
                if (!everyLineOrderedList && parsed[0].lineStart) {
                    const prevLineParsed = this.parseLine(parsed[0].lineStart - 1, parsed[0].lineStart - 1);

                    if (this.hasOrderedList(prevLineParsed.lineText)) {
                        number = this.getOrderedList(prevLineParsed.lineText) + 1;
                    }
                }

                for (i in parsed) {
                    ({ selectionStart, selectionEnd } = this.editor);

                    if (everyLineOrderedList) {
                        numberLength = (this.getOrderedList(parsed[i].lineText)).toString().length;
                        this.setRangeText(
                            "",
                            parsed[i].lineStart + addedTextLength,
                            parsed[i].lineStart + addedTextLength + numberLength + 2,
                        );
                        this.setSelectionRange(
                            parsed[i].lineStart < selectionStart
                                ? selectionStart - numberLength - 2
                                : selectionStart,
                            selectionEnd - numberLength - 2,
                        );
                        addedTextLength -= numberLength + 2;
                        continue;
                    }

                    if (!this.hasOrderedList(parsed[i].lineText)) {
                        const text = `${number}. `;
                        this.setRangeText(
                            text,
                            parsed[i].lineStart + addedTextLength,
                            parsed[i].lineStart + addedTextLength,
                        );
                        this.setSelectionRange(
                            parsed[i].lineStart < selectionStart
                                ? selectionStart + text.length
                                : selectionStart,
                            selectionEnd + text.length,
                        );
                        addedTextLength += text.length;
                    } else if (this.getOrderedList(parsed[i].lineText) != number) {
                        numberLength = (this.getOrderedList(parsed[i].lineText)).toString().length;
                        this.setRangeText(
                            number,
                            parsed[i].lineStart + addedTextLength,
                            parsed[i].lineStart + addedTextLength + numberLength,
                        );
                        this.setSelectionRange(
                            parsed[i].lineStart < selectionStart
                                ? selectionStart - (numberLength - (number).toString().length)
                                : selectionStart,
                            selectionEnd - (numberLength - (number).toString().length),
                        );
                        addedTextLength -= (numberLength - (number).toString().length);
                    }

                    number++;
                }
            },

            hasOrderedList(text) {
                return text.match(/^[\d]+\. /);
            },

            getOrderedList(text) {
                return parseInt(text.match(/\d+/)[0]);
            },

            addOrderedList(index, number) {
                const { selectionStart, selectionEnd } = this.editor;
                const text = `${number}. `;
                this.setRangeText(text, index, index);
                this.setSelectionRange(selectionStart + text.length, selectionEnd + text.length);
            },

            taskList() {
                const parsed = this.parseAllLines(this.editor.selectionStart, this.editor.selectionEnd);
                const everyLineTaskList = parsed.every(element => this.hasTaskList(element.lineText));
                let selectionStart, selectionEnd, addedTextLength = 0;

                for (i in parsed) {
                    ({ selectionStart, selectionEnd } = this.editor);

                    if (everyLineTaskList) {
                        this.setRangeText(
                            "",
                            parsed[i].lineStart + addedTextLength,
                            parsed[i].lineStart + addedTextLength + 6,
                        );
                        this.setSelectionRange(
                            parsed[i].lineStart < selectionStart
                                ? selectionStart - 6
                                : selectionStart,
                            selectionEnd - 6,
                        );
                        addedTextLength -= 6;
                        continue;
                    }

                    if (!this.hasTaskList(parsed[i].lineText)) {
                        const text = "- [ ] ";
                        this.setRangeText(
                            text,
                            parsed[i].lineStart + addedTextLength,
                            parsed[i].lineStart + addedTextLength,
                        );
                        this.setSelectionRange(
                            parsed[i].lineStart < selectionStart
                                ? selectionStart + text.length
                                : selectionStart,
                            selectionEnd + text.length,
                        );
                        addedTextLength += text.length;
                    }
                }
            },

            hasTaskList(text) {
                return /^- \[.{1}\] /.test(text);
            },

            addTaskList(index) {
                const { selectionStart, selectionEnd } = this.editor;
                const text = "- [ ] ";
                this.setRangeText(text, index, index);
                this.setSelectionRange(selectionStart + text.length, selectionEnd + text.length);
            },

            heading(level) {
                level = parseInt(level);

                if (level < 1 || level > 6) {
                    this.editor.focus();
                    return;
                }

                const parsed = this.parseLine(this.editor.selectionStart, this.editor.selectionEnd);
                const text = "#".repeat(level) + " ";

                if (this.hasHeading(parsed.lineText)) {
                    const headingLength = this.getHeading(parsed.lineText).length;

                    if (headingLength == text.length) {
                        this.editor.focus();
                        return;
                    }
                }

                this.deleteStyles();
                const { selectionStart, selectionEnd } = this.editor;
                this.setRangeText(text, parsed.lineStart, parsed.lineStart);
                this.setSelectionRange(selectionStart + text.length, selectionEnd + text.length);
            },

            hasHeading(text) {
                return /^[#]{1,6} /.test(text);
            },

            getHeading(text) {
                return text.match(/^[#]{1,6} /)[0];
            },

            addHeading(index, level) {
                const { selectionStart, selectionEnd } = this.editor;
                const text = "#".repeat(level) + " ";
                this.setRangeText(text, index, index);
                this.setSelectionRange(selectionStart + text.length, selectionEnd + text.length);
            },

            blockquote() {
                const parsed = this.parseAllLines(this.editor.selectionStart, this.editor.selectionEnd);
                const everyLineBlockquot = parsed.every(element => this.hasBlockquote(element.lineText));
                let selectionStart, selectionEnd, addedTextLength = 0;

                for (i in parsed) {
                    selectionStart = this.editor.selectionStart;
                    selectionEnd = this.editor.selectionEnd;

                    if (everyLineBlockquot) {
                        this.setRangeText(
                            "",
                            parsed[i].lineStart + addedTextLength,
                            parsed[i].lineStart + addedTextLength + 2,
                        );
                        this.setSelectionRange(
                            parsed[i].lineStart < selectionStart
                                ? selectionStart - 2
                                : selectionStart,
                            selectionEnd - 2,
                        );
                        addedTextLength -= 2;
                        continue;
                    }

                    if (!this.hasBlockquote(parsed[i].lineText)) {
                        const text = "> ";
                        this.setRangeText(
                            text,
                            parsed[i].lineStart + addedTextLength,
                            parsed[i].lineStart + addedTextLength,
                        );
                        this.setSelectionRange(
                            parsed[i].lineStart < selectionStart
                                ? selectionStart + text.length
                                : selectionStart,
                            selectionEnd + text.length,
                        );
                        addedTextLength += 2;
                    }
                }
            },

            hasBlockquote(text) {
                return /^> /.test(text);
            },

            bold() {
                this.parseSelection();
                const parsed = this.parseLine(this.editor.selectionStart, this.editor.selectionEnd);

                // Find the positions of two consecutive '*' characters
                const prefixFound = parsed.selectionPrefixText.search(/[\*]{2}/);
                const suffixFound = parsed.selectionSuffixText.search(/[\*]{2}/);

                if (prefixFound != -1 && suffixFound != -1) {
                    this.setRangeText(
                        "",
                        parsed.fullSelectionStart + prefixFound,
                        parsed.fullSelectionStart + prefixFound + 2,
                    );
                    this.setRangeText(
                        "",
                        parsed.selectionEnd + suffixFound - 2,
                        parsed.selectionEnd + suffixFound,
                    );
                } else {
                    this.setRangeText("**", parsed.selectionStart, parsed.selectionStart);
                    this.setRangeText("**", parsed.selectionEnd + 2, parsed.selectionEnd + 2);
                    this.setSelectionRange(parsed.selectionStart + 2, parsed.selectionEnd + 2);
                }
            },

            italic() {
                this.parseSelection();
                const parsed = this.parseLine(this.editor.selectionStart, this.editor.selectionEnd);

                // Find the positions of non-consecutive '*' characters (ignore ** because it's for bold)
                const prefixSingleFound = parsed.selectionPrefixText.search(/(?<!\*)\*(?!\*)/);
                const suffixSingleFound = parsed.selectionSuffixText.search(/(?<!\*)\*(?!\*)/);

                // Find the positions of three consecutive '*' characters
                const prefixTripleFound = parsed.selectionPrefixText.search(/[\*]{3}/);
                const suffixTripleFound = parsed.selectionSuffixText.search(/[\*]{3}/);

                if (prefixSingleFound != -1 && suffixSingleFound != -1) {
                    this.setRangeText(
                        "",
                        parsed.fullSelectionStart + prefixSingleFound,
                        parsed.fullSelectionStart + prefixSingleFound + 1,
                    );
                    this.setRangeText(
                        "",
                        parsed.selectionEnd + suffixSingleFound - 1,
                        parsed.selectionEnd + suffixSingleFound,
                    );
                } else if (prefixTripleFound != -1 && suffixTripleFound != -1) {
                    this.setRangeText(
                        "",
                        parsed.fullSelectionStart + prefixTripleFound,
                        parsed.fullSelectionStart + prefixTripleFound + 1,
                    );
                    this.setRangeText(
                        "",
                        parsed.selectionEnd + suffixTripleFound - 1,
                        parsed.selectionEnd + suffixTripleFound,
                    );
                } else {
                    this.setRangeText("*", parsed.selectionStart, parsed.selectionStart);
                    this.setRangeText("*", parsed.selectionEnd + 1, parsed.selectionEnd + 1);
                    this.setSelectionRange(parsed.selectionStart + 1, parsed.selectionEnd + 1);
                }
            },

            strikethrough() {
                this.parseSelection();
                const parsed = this.parseLine(this.editor.selectionStart, this.editor.selectionEnd);

                // Find the positions of two consecutive '~' characters
                const prefixFound = parsed.selectionPrefixText.search(/[~]{2}/);
                const suffixFound = parsed.selectionSuffixText.search(/[~]{2}/);

                if (prefixFound != -1 && suffixFound != -1) {
                    this.setRangeText(
                        "",
                        parsed.fullSelectionStart + prefixFound,
                        parsed.fullSelectionStart + prefixFound + 2,
                    );
                    this.setRangeText(
                        "",
                        parsed.selectionEnd + suffixFound - 2,
                        parsed.selectionEnd + suffixFound,
                    );
                } else {
                    this.setRangeText("~~", parsed.selectionStart, parsed.selectionStart);
                    this.setRangeText("~~", parsed.selectionEnd + 2, parsed.selectionEnd + 2);
                    this.setSelectionRange(parsed.selectionStart + 2, parsed.selectionEnd + 2);
                }
            },

            link(name = '', url = '') {
                this.parseSelection();
                const { selectionStart, selectionEnd } = this.editor;
                let alt = this.editor.value.substring(selectionStart, selectionEnd);

                if (alt.length === 0) {
                    alt = name;
                }

                const text = `[${alt}](${url})`;
                let moveSelection = !alt.length ? 1 : alt.length + 3;

                if (alt.length && url.length) {
                    moveSelection += url.length + 1;
                }

                this.setRangeText(text, selectionStart, selectionEnd);
                this.setSelectionRange(selectionStart + moveSelection, selectionStart + moveSelection);
            },

            image(name = '', url = '') {
                this.parseSelection();
                const { selectionStart, selectionEnd } = this.editor;
                let alt = this.editor.value.substring(selectionStart, selectionEnd);

                if (alt.length === 0) {
                    alt = name;
                }

                const text = `![${alt}](${url})`;
                let moveSelection = !alt.length ? 2 : alt.length + 4;

                if (alt.length && url.length) {
                    moveSelection += url.length + 1;
                }

                this.setRangeText(text, selectionStart, selectionEnd);
                this.setSelectionRange(selectionStart + moveSelection, selectionStart + moveSelection);
            },

            table() {
                const columns = parseInt(Math.abs(prompt('{{ __('Number of columns?') }}')));
                if (isNaN(columns) || columns == 0) {
                    return;
                }

                const rows = parseInt(Math.abs(prompt('{{ __('Number of rows?') }}')));
                if (isNaN(rows) || rows == 0) {
                    return;
                }

                const selectionEnd = this.editor.selectionEnd;
                let text = '';

                for (let i = 0; i < rows; i++) {
                    if (i) {
                        text += `{{ PHP_EOL }}`;
                    }

                    text += this.tableRow(columns, '  ');

                    if (!i) {
                        text += `{{ PHP_EOL }}` + this.tableRow(columns, '---');
                    }
                }

                this.setRangeText(text, selectionEnd, selectionEnd);
                this.setSelectionRange(selectionEnd + 2, selectionEnd + 2);
            },

            tableRow(columns, defaultText) {
                let text = '|';

                for (let i = 0; i < columns; i++) {
                    text += `${defaultText}|`;
                }

                return text;
            }
        }));
    </script>
@endscript
