const assert = require('assert');

class FakeInput {
    constructor(topId) {
        this.topId = String(topId);
        this.focused = false;
        this.selected = false;
    }

    getAttribute(name) {
        return name === 'data-top-id' ? this.topId : null;
    }

    focus() {
        this.focused = true;
    }

    select() {
        this.selected = true;
    }
}

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
require('../../js/components/agenda-list.js');
require('../../js/components/meeting-list.js');
require('../../js/components/meeting-detail.js');

const { meetingDetail, meetingList } = window.BRTop;

assert(meetingList.sessionTableHtml([]).includes('Noch keine Sitzung vorhanden.'));

const meetingListHtml = meetingList.sessionTableHtml([
    {
        id: 7,
        title: 'Sitzung <A>',
        meeting_date: '2026-07-07',
        meeting_time: '09:30:00'
    }
]);

assert(meetingListHtml.includes('07.07.2026'));
assert(meetingListHtml.includes('09:30'));
assert(meetingListHtml.includes('Sitzung &lt;A&gt;'));
assert(!meetingListHtml.includes('Sitzung <A>'));
assert(meetingListHtml.includes('data-action="edit-meeting"'));
assert(meetingListHtml.includes('data-action="delete-meeting"'));
assert(meetingListHtml.includes('data-id="7"'));

const editInput = new FakeInput(11);
const heading = { textContent: '' };
const content = {
    innerHTML: '',
    querySelectorAll(selector) {
        assert.strictEqual(selector, '[data-top-edit-input]');

        return [editInput];
    }
};
let meeting = {
    title: 'Sitzung <Detail>',
    meeting_date: '2026-07-07',
    meeting_time: '09:30:00',
    location: 'Raum <1>',
    meeting_type: 'regular',
    committee_code: 'BA',
    invitation_date: '2026-07-04',
    invitation_status: 'draft',
    tops: [
        {
            id: 11,
            position: 1,
            level: 4,
            subject: 'TOP <One>',
            agenda_item_kind: 'resolution',
            requires_resolution: 1,
            resolution_count: 2,
            legal_basis: 'Paragraf <99>',
            invitation_note: 'Bitte <lesen>',
            attachment_paths: 'a.pdf\nb<z>.pdf'
        }
    ],
    documents: [
        {
            title: 'Dokument <A>',
            file_path: '/BRTop/<A>.odt'
        }
    ]
};

const controller = meetingDetail.createController({
    byId(id) {
        if (id === 'meeting-detail-heading') {
            return heading;
        }
        if (id === 'meeting-detail-content') {
            return content;
        }

        return null;
    },
    getMeeting: () => meeting,
    getSettings: () => ({
        meetingTypes: [
            { value: 'regular', label: 'Regulaere Sitzung <X>' }
        ]
    }),
    getEditingTopId: () => 11
});

controller.render();

assert.strictEqual(heading.textContent, 'Sitzung <Detail>');
assert(content.innerHTML.includes('07.07.2026 · 09:30 · Raum &lt;1&gt; · Regulaere Sitzung &lt;X&gt; · Ausschuss: BA · Ladung: 04.07.2026 · Status: draft'));
assert(!content.innerHTML.includes('Raum <1>'));
assert(content.innerHTML.includes('brtop-agenda-level-3'));
assert(content.innerHTML.includes('value="TOP &lt;One&gt;"'));
assert(!content.innerHTML.includes('TOP <One>'));
assert(content.innerHTML.includes('2 Beschl&uuml;sse') || content.innerHTML.includes('2 Beschl\u00fcsse'));
assert(content.innerHTML.includes('Paragraf &lt;99&gt;'));
assert(content.innerHTML.includes('Bitte &lt;lesen&gt;'));
assert(content.innerHTML.includes('Anh\u00e4nge: a.pdf; b&lt;z&gt;.pdf') || content.innerHTML.includes('Anh&auml;nge: a.pdf; b&lt;z&gt;.pdf'));
assert(content.innerHTML.includes('Dokument &lt;A&gt;'));
assert(content.innerHTML.includes('/BRTop/&lt;A&gt;.odt'));
assert.strictEqual(editInput.focused, true);
assert.strictEqual(editInput.selected, true);
assert.strictEqual(controller.editingInput(11), editInput);
assert.strictEqual(controller.editingInput(12), null);

meeting = null;
controller.render();

assert.strictEqual(heading.textContent, 'Sitzung');
assert(content.innerHTML.includes('Die Sitzung wurde nicht gefunden.'));

console.log('BRTop meeting components smoke test passed.');
