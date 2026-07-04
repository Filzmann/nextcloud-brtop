const assert = require('assert');

const uiState = {
    notices: [],
    errors: []
};

class FakeElement {
    constructor(attributes = {}) {
        this.attributes = attributes;
        this.listeners = {};
        this.dataset = {};
    }

    addEventListener(type, listener) {
        this.listeners[type] = listener;
    }

    closest(selector) {
        if (selector === 'button[data-action]' && this.attributes['data-action']) {
            return this;
        }

        return null;
    }

    getAttribute(name) {
        return this.attributes[name] || null;
    }

    matches(selector) {
        return selector === '[data-top-edit-input]' && this.attributes['data-top-edit-input'] !== undefined;
    }
}

class FakeInput extends FakeElement {
    constructor(attributes = {}, value = '') {
        super(attributes);
        this.value = value;
        this.focused = false;
    }

    focus() {
        this.focused = true;
    }
}

global.window = {
    BRTop: {
        ui: {
            showNotice(message, type = 'info') {
                uiState.notices.push({ message, type });
            },
            showError(error, fallback) {
                uiState.errors.push({ message: error.message, fallback });
            }
        }
    }
};
global.Element = FakeElement;
global.HTMLInputElement = FakeInput;
global.confirm = () => true;

require('../../js/components/agenda-editor.js');

const content = new FakeElement();
const addButton = new FakeElement();
const calls = [];
const byId = (id) => {
    if (id === 'meeting-detail-content') {
        return content;
    }
    if (id === 'add-top') {
        return addButton;
    }

    return null;
};
const repository = {
    async addTop(meetingId, payload) {
        calls.push(['addTop', meetingId, payload]);
    },
    async saveTopSubject(meetingId, topId, subject) {
        calls.push(['saveTopSubject', meetingId, topId, subject]);
    },
    async moveTop(meetingId, topId, direction) {
        calls.push(['moveTop', meetingId, topId, direction]);
    },
    async changeTopDepth(meetingId, topId, direction) {
        calls.push(['changeTopDepth', meetingId, topId, direction]);
    },
    async deleteTop(meetingId, topId) {
        calls.push(['deleteTop', meetingId, topId]);
    }
};
const topForm = {
    cleared: false,
    hidden: false,
    payload() {
        return { subject: 'TOP 1' };
    },
    clear() {
        this.cleared = true;
    },
    hide() {
        this.hidden = true;
    }
};
const input = new FakeInput({ 'data-top-edit-input': '', 'data-top-id': '7' }, '  ');
const meetingDetail = {
    editingInput() {
        return input;
    }
};
let meetingId = null;
let stateLoads = 0;
let renders = 0;
let editingTopId = null;

const controller = window.BRTop.agendaEditor.createController({
    byId,
    repository,
    topForm,
    meetingDetail,
    getMeetingId: () => meetingId,
    setEditingTopId: (id) => {
        editingTopId = id;
    },
    clearEditingTopId: () => {
        editingTopId = null;
    },
    loadState: async () => {
        stateLoads += 1;
    },
    renderMeetingDetail: () => {
        renders += 1;
    }
});
controller.init();

(async () => {
    await addButton.listeners.click();
    assert.deepStrictEqual(uiState.notices.pop(), {
        message: 'Bitte zuerst eine Sitzung oeffnen.',
        type: 'error'
    });
    assert.deepStrictEqual(calls, []);

    meetingId = 5;
    await addButton.listeners.click();
    assert.deepStrictEqual(calls.shift(), ['addTop', 5, { subject: 'TOP 1' }]);
    assert.strictEqual(topForm.cleared, true);
    assert.strictEqual(topForm.hidden, true);
    assert.strictEqual(stateLoads, 1);
    assert.strictEqual(renders, 1);
    assert.deepStrictEqual(uiState.notices.pop(), {
        message: 'TOP gespeichert.',
        type: 'success'
    });

    let prevented = false;
    await content.listeners.keydown({
        target: input,
        key: 'Enter',
        preventDefault() {
            prevented = true;
        }
    });
    assert.strictEqual(prevented, true);
    assert.strictEqual(input.focused, true);
    assert.deepStrictEqual(uiState.notices.pop(), {
        message: 'Der TOP-Betreff darf nicht leer sein.',
        type: 'error'
    });

    await content.listeners.click({
        target: new FakeElement({
            'data-action': 'move-top',
            'data-top-id': '9',
            'data-direction': 'up'
        })
    });
    assert.deepStrictEqual(calls.pop(), ['moveTop', 5, '9', 'up']);

    await content.listeners.click({
        target: new FakeElement({
            'data-action': 'edit-top',
            'data-top-id': '11'
        })
    });
    assert.strictEqual(editingTopId, '11');

    console.log('BRTop agenda editor smoke test passed.');
})().catch((error) => {
    console.error(error);
    process.exit(1);
});
