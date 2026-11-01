const assert = require('assert');

const element = () => ({
    children: [], options: [], selectedOptions: [], textContent: '', listeners: {}, value: '',
    addEventListener(type, callback) { this.listeners[type] = callback; },
    append(child) { this.children.push(child); this.options.push(child); },
    replaceChildren() { this.children = []; this.options = []; },
});
const form = element();
const select = element();
const status = element();
const calls = [];
global.window = { BRTop: { api: { request: async (path, options = {}) => {
    calls.push([path, options.method || 'GET', options.body || null]);
    if ((options.method || 'GET') === 'GET') return { members: [{ uid: 'member-a', displayName: 'Mitglied A', assigned: true }] };
    return { assignedUids: ['member-a'] };
} } } };
global.document = {
    getElementById: id => ({
        'brtop-br-dpo-form': form,
        'brtop-br-dpo-member-select': select,
        'brtop-br-dpo-status': status,
    }[id] || null),
    createElement: () => element(),
};
require('../../js/br-dpo-settings.js');

setImmediate(async () => {
    assert.strictEqual(select.options.length, 1);
    assert.strictEqual(select.options[0].value, 'member-a');
    assert.strictEqual(select.options[0].selected, true);
    select.selectedOptions = [{ value: 'member-a' }];
    await form.listeners.submit({ preventDefault() {} });
    assert.deepStrictEqual(calls.map(([path, method]) => [path, method]), [
        ['/api/permissions/br-dpo', 'GET'],
        ['/api/permissions/br-dpo', 'POST'],
        ['/api/permissions/br-dpo', 'GET'],
    ]);
    assert(JSON.parse(calls[1][2]).memberUidsJson.includes('member-a'));
    console.log('BRTop BR-DPO settings UI smoke test passed.');
});
