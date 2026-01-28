import './bootstrap';
import SignaturePad from 'signature_pad';
import Sortable from 'sortablejs';
import * as Trix from 'trix';
import 'trix/dist/trix.css';

window.SignaturePad = SignaturePad;
window.Sortable = Sortable;
window.Trix = Trix;

document.addEventListener("trix-initialize", function(event) {});

document.addEventListener("trix-file-accept", function(event) {
    event.preventDefault();
});
