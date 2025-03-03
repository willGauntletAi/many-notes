import './bootstrap';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import './audioRecorder';
import './whisperTranscriber';

window.marked = marked;
window.DOMPurify = DOMPurify;
