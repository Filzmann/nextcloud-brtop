const assert = require('assert');

const elements = new Map([
    ['brtop-notice', { textContent: '', hidden: true, className: 'brtop-notice' }]
]);

global.window = {};
global.document = {
    getElementById(id) {
        return elements.get(id) || null;
    }
};

require('../../../localbase/js/ui/ui.js');
require('../../js/modules/ui.js');

const {
    documentResultText,
    showDocumentResult,
    showError,
    showNotice
} = window.BRTop.ui;
const notice = elements.get('brtop-notice');

assert.strictEqual(
    documentResultText({
        folder: '/BRTop',
        created: ['Einladung.md'],
        message: 'Fertig',
        warnings: ['Hinweis']
    }),
    'Ordner: /BRTop\n\nErzeugt:\nEinladung.md\n\nFertig\n\nHinweise:\nHinweis'
);

showNotice('Bereit', 'info');
assert.strictEqual(notice.textContent, 'Bereit');
assert.strictEqual(notice.hidden, false);
assert.strictEqual(notice.className, 'brtop-notice brtop-notice-info');

showError({ data: { message: 'API kaputt' }, message: 'HTTP 500' }, 'Fallback');
assert.strictEqual(notice.textContent, 'API kaputt');
assert.strictEqual(notice.className, 'brtop-notice brtop-notice-error');

showDocumentResult({ warnings: ['Achtung'] }, 'Erzeugt');
assert.strictEqual(notice.textContent, 'Hinweise:\nAchtung');
assert.strictEqual(notice.className, 'brtop-notice brtop-notice-warning');

showDocumentResult({}, 'Erzeugt');
assert.strictEqual(notice.textContent, 'Erzeugt');
assert.strictEqual(notice.className, 'brtop-notice brtop-notice-success');

console.log('BRTop UI smoke test passed.');
