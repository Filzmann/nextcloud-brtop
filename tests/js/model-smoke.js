const assert = require('assert');

global.window = {};

require('../../../localbase/js/models/model.js');

window.BRTop = { models: {} };

require('../../js/models/protocol-block.js');
require('../../js/models/generated-document.js');
require('../../js/models/agenda-item.js');
require('../../js/models/meeting.js');

const { AgendaItem, GeneratedDocument, Meeting, ProtocolBlock } = window.BRTop.models;

const block = ProtocolBlock.get({
    id: 1,
    top_id: 7,
    content: 'Protokoll'
});

assert(block instanceof ProtocolBlock);
assert.strictEqual(block.id, 1);
assert.strictEqual(block.toArray().content, 'Protokoll');

const agendaItem = AgendaItem.get({
    id: 7,
    subject: 'Personelle Angelegenheit',
    requires_resolution: 1,
    protocol_blocks: [block.toArray()]
});

assert(agendaItem instanceof AgendaItem);
assert.strictEqual(agendaItem.protocol_blocks.length, 1);
assert.strictEqual(agendaItem.kindLabel(), 'Beschluss');

const document = GeneratedDocument.get({
    id: 3,
    document_type: 'protocol_odt'
});

assert(document instanceof GeneratedDocument);
assert.strictEqual(document.typeLabel(), 'Protokollvorlage ODT');

const meeting = Meeting.get({
    id: 5,
    legislature_id: 3,
    title: 'BR-Sitzung',
    tops: [agendaItem.toArray()],
    documents: [document.toArray()],
    invitation_recipients: [{ user_uid: 'simon', display_name: 'Simon' }]
});

assert(meeting instanceof Meeting);
assert.strictEqual(meeting.tops.length, 1);
assert.strictEqual(meeting.documents.length, 1);
assert.strictEqual(meeting.toArray().tops[0].subject, 'Personelle Angelegenheit');
assert.strictEqual(meeting.toArray().invitation_recipients[0].user_uid, 'simon');
assert.strictEqual(meeting.toArray().legislature_id, 3);

console.log('BRTop model smoke test passed.');
