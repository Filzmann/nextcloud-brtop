const assert = require('assert');
const {
    FakeButton,
    FakeElement,
} = require('../../../localbase/tests/js/helpers/fake-dom.js');

const uiState = {
    errors: []
};

class FakeTextArea extends FakeElement {
    constructor(dataset, value, row) {
        super();
        this.dataset = dataset;
        this.value = value;
        this.row = row;
        this.focused = false;
    }

    closest(selector) {
        if (selector === '.brtop-protocol-block-row') {
            return this.row;
        }

        return null;
    }

    focus() {
        this.focused = true;
    }
}

class FakeStatus {
    constructor(textContent = '') {
        this.textContent = textContent;
    }
}

class FakeRow {
    constructor(status) {
        this.status = status;
    }

    querySelector(selector) {
        if (selector === '.brtop-block-status') {
            return this.status;
        }

        return null;
    }
}

class FakeEditor extends FakeElement {
    constructor(textareas = []) {
        super();
        this.textareas = textareas;
    }

    querySelectorAll(selector) {
        if (selector === 'textarea[data-action="protocol-block-content"]') {
            return this.textareas;
        }

        return [];
    }
}

global.window = {
    BRTop: {
        ui: {
            esc(value) {
                return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[char]));
            },
            agendaKindLabel() {
                return 'Beratung';
            },
            agendaNumber(top) {
                return String(top.position || top.id) + '.';
            },
            buttonPresetHtml(preset, attrs) {
                return `<button data-action="add-protocol-block" data-top-id="${attrs['data-top-id']}">${preset}</button>`;
            },
            showError(error, fallback) {
                uiState.errors.push({ message: error.message, fallback });
            }
        }
    }
};
global.Element = FakeElement;
global.HTMLTextAreaElement = FakeTextArea;

require('../../js/components/protocol-editor.js');

const html = window.BRTop.protocolEditor.protocolEditorHtml([
    { id: 7, position: 1, subject: '<TOP>', protocol_blocks: [{ id: 3, content: '<Text>' }] }
]);
assert(html.includes('&lt;TOP&gt;'));
assert(html.includes('&lt;Text&gt;'));

const status = new FakeStatus('Gespeichert');
const row = new FakeRow(status);
const textarea = new FakeTextArea(
    { action: 'protocol-block-content', topId: '7', blockId: '3' },
    'Alt',
    row
);
const editor = new FakeEditor([textarea]);
let saveCalls = [];
let loadCalls = 0;
let renderCalls = 0;
const repository = {
    async saveProtocolBlock(meetingId, topId, blockId, content) {
        saveCalls.push({ meetingId, topId, blockId, content });
    },
    async addProtocolBlock(meetingId, topId) {
        return { block: { id: 99 }, meetingId, topId };
    }
};
const controller = window.BRTop.protocolEditor.createController({
    byId: () => editor,
    repository,
    getMeetingId: () => 5,
    loadState: async () => {
        loadCalls += 1;
    },
    render: () => {
        renderCalls += 1;
    }
});

controller.init();

(async () => {
    controller.afterRender();
    assert.strictEqual(textarea.dataset.lastSaved, 'Alt');
    assert.strictEqual(textarea.dataset.dirty, '0');

    textarea.value = 'Neu';
    textarea.dataset.dirty = '1';
    await controller.saveDirty();
    assert.deepStrictEqual(saveCalls.pop(), {
        meetingId: 5,
        topId: '7',
        blockId: '3',
        content: 'Neu'
    });
    assert.strictEqual(textarea.dataset.lastSaved, 'Neu');
    assert.strictEqual(textarea.dataset.dirty, '0');
    assert.strictEqual(status.textContent, 'Gespeichert');

    await editor.listeners.click({
        target: new FakeButton({
            action: 'add-protocol-block',
            topId: '7'
        })
    });
    assert.strictEqual(loadCalls, 1);
    assert.strictEqual(renderCalls, 1);

    editor.textareas.push(new FakeTextArea(
        { action: 'protocol-block-content', topId: '7', blockId: '99' },
        'Neu',
        new FakeRow(new FakeStatus('Gespeichert'))
    ));
    controller.afterRender();
    assert.strictEqual(editor.textareas[1].focused, true);

    repository.addProtocolBlock = async () => {
        throw new Error('Kaputt');
    };
    await editor.listeners.click({
        target: new FakeButton({
            action: 'add-protocol-block',
            topId: '7'
        })
    });
    assert.deepStrictEqual(uiState.errors.pop(), {
        message: 'Kaputt',
        fallback: 'Protokollblock konnte nicht hinzugefuegt werden.'
    });

    console.log('BRTop protocol editor smoke test passed.');
})().catch((error) => {
    console.error(error);
    process.exit(1);
});
