const assert = require('assert');

global.window = {};
global.document = { getElementById() { return null; } };
require('../../../localbase/js/ui/ui.js');
require('../../js/modules/ui.js');
require('../../js/components/absence-review.js');

const output = window.BRTop.absenceReview.html({
    meeting_date: '2026-07-21',
    reviewed: false,
    snapshot_locked: false,
    members: [
        { id: 1, display_name: 'Mitglied <Eins>', list_name: 'Liste <A>', list_rank: 1, member_role: 'regular', absence_status: 'suggested' },
        { id: 2, display_name: 'Ersatz Zwei', list_name: 'Liste B', list_rank: 8, member_role: 'replacement', absence_status: '' }
    ]
});

assert(output.includes('Kalenderdaten sind nur eine Vorbelegung'));
assert(output.includes('Mitglied &lt;Eins&gt;'));
assert(!output.includes('Mitglied <Eins>'));
assert(output.includes('Kalendervorschlag'));
assert(output.includes('value="1" checked'));
assert(output.includes('<caption>'));
assert(output.includes('scope="col"'));

const locked = window.BRTop.absenceReview.html({ meeting_date: '2026-07-21', snapshot_locked: true, members: [] });
assert(locked.includes('Versiegelt'));
assert(!locked.includes('Verhinderungen verbindlich bestätigen'));

console.log('BRTop absence review smoke test passed.');
