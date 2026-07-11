const assert = require('assert');

global.window = {};
global.document = { getElementById() { return null; } };
require('../../../localbase/js/ui/ui.js');
require('../../js/modules/ui.js');
require('../../js/components/legislature-editor.js');

const { html, emptyConfiguration } = window.BRTop.legislatureEditor;
const draft = emptyConfiguration();
draft.id = 5;
draft.name = 'Legislatur <2026>';
draft.lists[0].name = 'Liste <Zukunft>';
draft.lists[0].members.push({
    user_uid: 'member-1', display_name: 'Mitglied <Eins>', email: 'one@example.invalid',
    gender: 'female', member_role: 'regular', list_rank: 1
});

const draftHtml = html(draft);
assert(draftHtml.includes('Legislatur &lt;2026&gt;'));
assert(!draftHtml.includes('Legislatur <2026>'));
assert(draftHtml.includes('<fieldset'));
assert(draftHtml.includes('<legend>Vorschlagsliste 1</legend>'));
assert(draftHtml.includes('<caption>Mitglieder der Liste &lt;Zukunft&gt;</caption>'));
assert(draftHtml.includes('scope="col"'));
assert(draftHtml.includes('Mitglied &lt;Eins&gt; entfernen'));
assert(draftHtml.includes('Legislatur aktivieren und versiegeln'));

draft.status = 'active';
const activeHtml = html(draft);
assert(activeHtml.includes('aktiv und versiegelt'));
assert(!activeHtml.includes('Entwurf speichern'));
assert(!activeHtml.includes('data-action="remove-member"'));
assert(activeHtml.includes('Neue Legislatur als Entwurf vorbereiten'));

console.log('BRTop legislature editor smoke test passed.');
