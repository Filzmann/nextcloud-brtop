const assert = require('assert');

const calls = [];
global.window = {};
require('../../../localbase/js/repositories/repository.js');
window.BRTop = {};
require('../../js/repositories/legislature-repository.js');

(async () => {
    const repository = new window.BRTop.repositories.LegislatureRepository((path, options = {}) => {
        calls.push({ path, options });
        return Promise.resolve({ ok: true, legislature: null });
    });

    await repository.load();
    await repository.save({ name: 'Legislatur <A>' });
    await repository.activate(5);
    await repository.absences(9);
    await repository.saveAbsences(9, [11, 12]);

    assert.deepStrictEqual(calls.map(call => call.path), [
        '/api/legislature',
        '/api/legislature',
        '/api/legislature/5/activate',
        '/api/meetings/9/absences',
        '/api/meetings/9/absences'
    ]);
    assert.strictEqual(calls[1].options.body, '{"configurationJson":"{\\"name\\":\\"Legislatur <A>\\"}"}');
    assert.strictEqual(calls[4].options.body, '{"memberIdsJson":"[11,12]"}');

    console.log('BRTop legislature repository smoke test passed.');
})();
