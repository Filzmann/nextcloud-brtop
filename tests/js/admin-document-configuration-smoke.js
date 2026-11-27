const assert = require('assert');

function element() {
    return {
        dataset: {}, disabled: false, hidden: false, textContent: '', innerHTML: '', value: '', files: [], listeners: {},
        addEventListener(type, callback) { this.listeners[type] = callback; }, focus() {},
        querySelector(selector) { return this.queries?.[selector] || null; },
        querySelectorAll(selector) { return this.queryAll?.[selector] || []; },
    };
}

const form = element();
const templateList = element();
const editor = element();
const preview = element();
const status = element();
const previewButton = element();
const saveDraft = element();
const approve = element();
const calls = [];
const fields = {
    councilName: { value: 'Betriebsrat Beispiel' }, councilAddress: { value: 'Musterstraße 1' }, issuePlace: { value: 'Berlin' },
    committeeCodesJson: { value: '[{"value":"ASA","label":"ASA"}]' }, invitationSalutation: { value: 'Guten Tag,' },
    invitationIntroduction: { value: 'Einladung' }, invitationClosing: { value: 'Viele Grüße' }, invitationSender: { value: 'Betriebsrat' },
};
form.queries = Object.fromEntries(Object.entries(fields).map(([name, field]) => [`[name="${name}"]`, field]));

global.window = { BRTop: { api: { request: async (path, options = {}) => {
    calls.push([path, options.method || 'GET', options.body || null]);
    if (path === '/api/admin/document-configuration') return {
        organization: Object.fromEntries(Object.entries(fields).map(([name, field]) => [name, field.value])),
        templates: [{ templateKey: 'protocol_markdown', version: 0, format: 'markdown', status: 'built_in' }],
    };
    if (path === '/api/admin/document-configuration/templates/protocol_markdown/0') return { template: { templateKey: 'protocol_markdown', version: 0, format: 'markdown', content: '{{PROTOCOL_BODY}}', status: 'built_in' } };
    if (path.endsWith('/preview')) return { preview: { format: 'markdown', content: 'Vorschau' } };
    if (path.endsWith('/draft')) return { template: { templateKey: 'protocol_markdown', version: 1, format: 'markdown', status: 'draft' } };
    if (path.endsWith('/approve')) return { template: { templateKey: 'protocol_markdown', version: 1, format: 'markdown', status: 'approved' } };
    return { organization: {} };
} } } };
global.document = { getElementById(id) { return ({
    'brtop-document-organization-form': form, 'brtop-document-template-list': templateList,
    'brtop-document-template-editor': editor, 'brtop-document-template-preview': preview, 'brtop-document-template-editor-heading': element(),
    'brtop-document-template-meta': element(), 'brtop-document-template-content-label': element(), 'brtop-document-template-file-label': element(),
    'brtop-document-template-content': Object.assign(element(), { value: '{{PROTOCOL_BODY}}' }), 'brtop-document-template-file': element(),
    'brtop-document-template-preview-button': previewButton,
    'brtop-document-configuration-status': status, 'brtop-document-template-save-draft': saveDraft,
    'brtop-document-template-approve': approve,
}[id] || null); } };

require('../../js/admin-document-configuration.js');

setImmediate(async () => {
    assert.deepStrictEqual(calls[0].slice(0, 2), ['/api/admin/document-configuration', 'GET']);
    await form.listeners.submit({ preventDefault() {} });
    await templateList.listeners.click({ target: { closest() { return { dataset: { templateKey: 'protocol_markdown', version: '0' } }; } } });
    await previewButton.listeners.click();
    await saveDraft.listeners.click();
    await approve.listeners.click();
    assert.deepStrictEqual(calls.map(([path, method]) => [path, method]), [
        ['/api/admin/document-configuration', 'GET'],
        ['/api/admin/document-configuration/organization', 'POST'],
        ['/api/admin/document-configuration/templates/protocol_markdown/0', 'GET'],
        ['/api/admin/document-configuration/templates/protocol_markdown/preview', 'POST'],
        ['/api/admin/document-configuration/templates/protocol_markdown/draft', 'POST'],
        ['/api/admin/document-configuration/templates/protocol_markdown/1/approve', 'POST'],
    ]);
    assert(status.textContent.includes('freigegeben'));
    console.log('BRTop document configuration UI smoke test passed.');
});
