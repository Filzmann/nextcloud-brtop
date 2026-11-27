const assert = require('assert');

const element = (id) => ({
    id,
    children: [],
    dataset: {},
    disabled: false,
    hidden: false,
    textContent: '',
    attributes: {},
    focused: false,
    listeners: {},
    append(child) { this.children.push(child); },
    addEventListener(type, callback) { (this.listeners[type] = this.listeners[type] || []).push(callback); },
    setAttribute(name, value) { this.attributes[name] = String(value); },
    getAttribute(name) { return name in this.attributes ? this.attributes[name] : null; },
    focus() { this.focused = true; },
    replaceChildren() { this.children = []; },
});

const form = element('brtop-full-access-form');
form.elements = { enabled: { checked: true } };
const history = element('brtop-full-access-history');
const status = element('brtop-full-access-status');
const overlay = element('brtop-admin-access-overlay');
overlay.hidden = true;
const trigger = element('brtop-admin-access-warning');
const closeButton = element('brtop-admin-access-overlay-close');
const doc = element('document');

const byId = {
    'brtop-full-access-form': form,
    'brtop-full-access-history': history,
    'brtop-full-access-status': status,
    'brtop-admin-access-overlay': overlay,
    'brtop-admin-access-warning': trigger,
    'brtop-admin-access-overlay-close': closeButton,
};

const calls = [];
global.window = { BRTop: { api: { request: async (path, options = {}) => { calls.push([path, options.method || 'GET', options.body || null]); return { history: [] }; } } } };
global.document = {
    getElementById: (id) => byId[id] || null,
    createElement: () => element('created'),
    addEventListener: (type, callback) => doc.addEventListener(type, callback),
    querySelectorAll: () => [],
    head: { appendChild() {} },
};
global.FormData = class { get(name) { return { enabled: 'on', targetUid: ' admin-target ', durationMinutes: '60' }[name]; } };

require('../../js/shared/overlay.js');
require('../../js/admin-access.js');

const fire = async (el, type, event) => { for (const callback of el.listeners[type] || []) await callback(event); };

setImmediate(async () => {
    // Ohne geöffnetes Overlay wird keine Historie geladen.
    assert.deepStrictEqual(calls, []);

    // Warn-Icon öffnet das Overlay und lädt die Historie.
    await fire(trigger, 'click', { target: trigger });
    assert.strictEqual(overlay.hidden, false);
    assert.strictEqual(trigger.getAttribute('aria-expanded'), 'true');
    assert.strictEqual(closeButton.focused, true);
    assert.deepStrictEqual(calls.map(([path, method]) => [path, method]), [['/api/admin/full-access', 'GET']]);

    // Freigabe erteilen: POST, danach erneutes Laden.
    await fire(form, 'submit', { preventDefault() {} });
    // Widerruf: DELETE, danach erneutes Laden.
    const revokeButton = { dataset: { revokeUid: 'admin-target' }, disabled: false };
    await fire(history, 'click', { target: { closest: () => revokeButton } });
    assert.ok(status.textContent.includes('widerrufen'));

    // Escape schließt das Overlay und gibt den Fokus an das Warn-Icon zurück.
    await fire(doc, 'keydown', { key: 'Escape' });
    assert.strictEqual(overlay.hidden, true);
    assert.strictEqual(trigger.getAttribute('aria-expanded'), 'false');
    assert.strictEqual(trigger.focused, true);

    // Schließen-Button und Backdrop-Klick schließen ebenfalls.
    await fire(trigger, 'click', { target: trigger });
    await fire(closeButton, 'click', {});
    assert.strictEqual(overlay.hidden, true);
    await fire(trigger, 'click', { target: trigger });
    await fire(overlay, 'click', { target: overlay });
    assert.strictEqual(overlay.hidden, true);

    assert.deepStrictEqual(calls.map(([path, method]) => [path, method]), [
        ['/api/admin/full-access', 'GET'],
        ['/api/admin/full-access', 'POST'],
        ['/api/admin/full-access', 'GET'],
        ['/api/admin/full-access/admin-target', 'DELETE'],
        ['/api/admin/full-access', 'GET'],
        ['/api/admin/full-access', 'GET'],
        ['/api/admin/full-access', 'GET'],
    ]);
    console.log('BRTop admin access overlay smoke test passed.');
});
