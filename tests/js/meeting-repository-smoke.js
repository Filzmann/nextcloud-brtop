const assert = require('assert');

const calls = [];

global.window = {};

require('../../../localbase/js/models/model.js');
require('../../../localbase/js/repositories/repository.js');

window.BRTop = { models: {} };

require('../../js/models/protocol-block.js');
require('../../js/models/generated-document.js');
require('../../js/models/agenda-item.js');
require('../../js/models/meeting.js');
require('../../js/repositories/meeting-repository.js');

(async () => {
    const { MeetingRepository } = window.BRTop.repositories;
    const repository = new MeetingRepository((path, options = {}) => {
        calls.push({ path, options });

        if (path === '/api/state') {
            return Promise.resolve({
                meetings: [{ id: 1, title: 'BR-Sitzung' }],
                settings: { defaultMeetingTitle: 'BR-Sitzung' }
            });
        }

        return Promise.resolve({ path, options });
    });

    const state = await repository.state();
    await repository.createNextRegular();
    await repository.addTop(1, { subject: 'TOP' });
    await repository.saveProtocolBlock(1, 2, 3, 'Text');

    assert.strictEqual(state.meetings[0] instanceof window.BRTop.models.Meeting, true);
    assert.deepStrictEqual(calls.map(call => call.path), [
        '/api/state',
        '/api/meetings/next-regular',
        '/api/meetings/1/tops',
        '/api/meetings/1/tops/2/protocol-blocks/3'
    ]);
    assert.strictEqual(calls[1].options.method, 'POST');
    assert.strictEqual(calls[2].options.body, '{"subject":"TOP"}');
    assert.strictEqual(calls[3].options.body, '{"content":"Text"}');

    console.log('BRTop meeting repository smoke test passed.');
})();
