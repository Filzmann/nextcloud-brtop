(function() {
    const { esc } = window.BRTop.ui;

    function emptyConfiguration() {
        return {
            name: '',
            starts_on: '',
            ends_on: '',
            election_type: 'list',
            council_size: 13,
            minority_gender: 'female',
            minority_minimum_seats: 7,
            absence_calendar_principal: 'principals/users/admin',
            absence_calendar_uri: '',
            status: 'draft',
            lists: [emptyList('Liste 1'), emptyList('Liste 2')]
        };
    }

    function emptyList(name = '') {
        return { name, seat_count: 0, vote_count: 0, members: [] };
    }

    function emptyMember(rank) {
        return {
            user_uid: '', display_name: '', email: '', gender: 'female',
            member_role: 'replacement', list_rank: rank
        };
    }

    const input = (id, label, value, type = 'text', attrs = '') => `
        <label class="brtop-field" for="${esc(id)}">
            <span>${esc(label)}</span>
            <input id="${esc(id)}" type="${esc(type)}" value="${esc(value ?? '')}" ${attrs}>
        </label>`;

    function memberRow(member, listIndex, memberIndex, locked) {
        const prefix = `leg-member-${listIndex}-${memberIndex}`;
        return `
            <tr data-member-index="${memberIndex}">
                <td>${input(`${prefix}-uid`, 'Nextcloud-UID', member.user_uid, 'text', `data-member-field="user_uid" ${locked ? 'disabled' : 'required'}`)}</td>
                <td>${input(`${prefix}-name`, 'Anzeigename', member.display_name, 'text', `data-member-field="display_name" ${locked ? 'disabled' : 'required'}`)}</td>
                <td>${input(`${prefix}-email`, 'E-Mail', member.email, 'email', `data-member-field="email" ${locked ? 'disabled' : ''}`)}</td>
                <td>
                    <label class="brtop-field" for="${prefix}-gender"><span>Geschlecht</span>
                        <select id="${prefix}-gender" data-member-field="gender" ${locked ? 'disabled' : ''}>
                            <option value="female" ${member.gender === 'female' ? 'selected' : ''}>weiblich</option>
                            <option value="male" ${member.gender === 'male' ? 'selected' : ''}>männlich</option>
                            <option value="diverse" ${member.gender === 'diverse' ? 'selected' : ''}>divers</option>
                        </select>
                    </label>
                </td>
                <td>
                    <label class="brtop-field" for="${prefix}-role"><span>Rolle</span>
                        <select id="${prefix}-role" data-member-field="member_role" ${locked ? 'disabled' : ''}>
                            <option value="regular" ${member.member_role === 'regular' ? 'selected' : ''}>festes Mitglied</option>
                            <option value="replacement" ${member.member_role === 'replacement' ? 'selected' : ''}>Ersatzmitglied</option>
                        </select>
                    </label>
                </td>
                <td>${input(`${prefix}-rank`, 'Listenrang', member.list_rank, 'number', `min="1" data-member-field="list_rank" ${locked ? 'disabled' : 'required'}`)}</td>
                <td>${locked ? '' : `<button type="button" data-action="remove-member" data-list-index="${listIndex}" data-member-index="${memberIndex}" aria-label="${esc(member.display_name || 'Mitglied')} entfernen">Entfernen</button>`}</td>
            </tr>`;
    }

    function listCard(list, listIndex, locked) {
        const members = (list.members || []).map((member, memberIndex) => memberRow(member, listIndex, memberIndex, locked)).join('');
        return `
            <fieldset class="brtop-legislature-list" data-list-index="${listIndex}">
                <legend>Vorschlagsliste ${listIndex + 1}</legend>
                <div class="brtop-form-grid">
                    ${input(`leg-list-${listIndex}-name`, 'Listenname', list.name, 'text', `data-list-field="name" ${locked ? 'disabled' : 'required'}`)}
                    ${input(`leg-list-${listIndex}-seats`, 'Gewonnene Sitze', list.seat_count, 'number', `min="0" data-list-field="seat_count" ${locked ? 'disabled' : 'required'}`)}
                    ${input(`leg-list-${listIndex}-votes`, 'Gültige Listenstimmen', list.vote_count, 'number', `min="0" data-list-field="vote_count" ${locked ? 'disabled' : 'required'}`)}
                </div>
                <div class="brtop-table-wrap" data-persistent-horizontal-scroll>
                    <table>
                        <caption>Mitglieder der ${esc(list.name || `Vorschlagsliste ${listIndex + 1}`)}</caption>
                        <thead><tr><th scope="col">UID</th><th scope="col">Name</th><th scope="col">E-Mail</th><th scope="col">Geschlecht</th><th scope="col">Rolle</th><th scope="col">Rang</th><th scope="col">Aktion</th></tr></thead>
                        <tbody>${members || '<tr><td colspan="7">Noch keine Mitglieder eingetragen.</td></tr>'}</tbody>
                    </table>
                </div>
                ${locked ? '' : `<div class="brtop-actions"><button type="button" data-action="add-member" data-list-index="${listIndex}">Mitglied hinzufügen</button><button type="button" data-action="remove-list" data-list-index="${listIndex}">Liste entfernen</button></div>`}
            </fieldset>`;
    }

    function html(configuration) {
        const data = configuration || emptyConfiguration();
        const locked = data.status === 'active';
        return `
            <form id="legislature-form">
                <p id="legislature-help">Die Konfiguration gilt für eine Legislatur. Nach der Aktivierung ist sie versiegelt. Bereits erzeugte Einladungssnapshots bleiben immer unverändert.</p>
                <p><strong>Status:</strong> ${locked ? 'aktiv und versiegelt' : 'Entwurf'}</p>
                <div class="brtop-form-grid">
                    ${input('leg-name', 'Bezeichnung', data.name, 'text', `data-root-field="name" ${locked ? 'disabled' : 'required'}`)}
                    ${input('leg-start', 'Beginn', data.starts_on, 'date', `data-root-field="starts_on" ${locked ? 'disabled' : 'required'}`)}
                    ${input('leg-end', 'Ende', data.ends_on, 'date', `data-root-field="ends_on" ${locked ? 'disabled' : 'required'}`)}
                    ${input('leg-size', 'BR-Größe', data.council_size, 'number', `min="3" data-root-field="council_size" ${locked ? 'disabled' : 'required'}`)}
                    <label class="brtop-field" for="leg-minority-gender"><span>Minderheitengeschlecht</span>
                        <select id="leg-minority-gender" data-root-field="minority_gender" ${locked ? 'disabled' : ''}>
                            <option value="female" ${data.minority_gender === 'female' ? 'selected' : ''}>weiblich</option>
                            <option value="male" ${data.minority_gender === 'male' ? 'selected' : ''}>männlich</option>
                            <option value="diverse" ${data.minority_gender === 'diverse' ? 'selected' : ''}>divers</option>
                        </select>
                    </label>
                    ${input('leg-minority-seats', 'Mindestsitze Minderheitengeschlecht', data.minority_minimum_seats, 'number', `min="0" data-root-field="minority_minimum_seats" ${locked ? 'disabled' : 'required'}`)}
                    ${input('leg-calendar-principal', 'Kalender-Principal', data.absence_calendar_principal, 'text', `data-root-field="absence_calendar_principal" ${locked ? 'disabled' : ''}`)}
                    ${input('leg-calendar-uri', 'Kalender-URI', data.absence_calendar_uri, 'text', `data-root-field="absence_calendar_uri" ${locked ? 'disabled' : ''}`)}
                </div>
                <div id="legislature-lists">${(data.lists || []).map((list, index) => listCard(list, index, locked)).join('')}</div>
                ${locked
                    ? '<div class="brtop-actions"><button type="button" data-action="new-legislature">Neue Legislatur als Entwurf vorbereiten</button></div>'
                    : '<div class="brtop-actions"><button type="button" data-action="add-list">Vorschlagsliste hinzufügen</button><button class="primary" type="submit">Entwurf speichern</button>' + (data.id ? '<button type="button" data-action="activate-legislature">Legislatur aktivieren und versiegeln</button>' : '') + '</div>'}
            </form>`;
    }

    function createController({ byId, repository, showNotice, showError }) {
        let configuration = null;

        function render() {
            byId('legislature-editor').innerHTML = html(configuration);
        }

        async function load() {
            const result = await repository.load();
            configuration = result.legislature || emptyConfiguration();
            render();
        }

        function readForm() {
            const root = byId('legislature-form');
            const result = { ...(configuration || emptyConfiguration()), lists: [] };
            root.querySelectorAll('[data-root-field]').forEach(field => {
                result[field.dataset.rootField] = field.type === 'number' ? Number(field.value) : field.value;
            });
            root.querySelectorAll('[data-list-index]').forEach(fieldset => {
                if (fieldset.tagName !== 'FIELDSET') return;
                const list = { members: [] };
                fieldset.querySelectorAll('[data-list-field]').forEach(field => {
                    list[field.dataset.listField] = field.type === 'number' ? Number(field.value) : field.value;
                });
                fieldset.querySelectorAll('tbody tr[data-member-index]').forEach(row => {
                    const member = {};
                    row.querySelectorAll('[data-member-field]').forEach(field => {
                        member[field.dataset.memberField] = field.type === 'number' ? Number(field.value) : field.value;
                    });
                    list.members.push(member);
                });
                result.lists.push(list);
            });
            result.election_type = 'list';
            result.status = configuration && configuration.status ? configuration.status : 'draft';
            return result;
        }

        async function handleSubmit(event) {
            event.preventDefault();
            try {
                const result = await repository.save(readForm());
                configuration = result.legislature;
                render();
                showNotice('Legislaturentwurf gespeichert.', 'success');
            } catch (error) {
                showError(error, 'Legislatur konnte nicht gespeichert werden.');
            }
        }

        async function handleClick(event) {
            const button = event.target.closest('button[data-action]');
            if (!button) return;
            configuration = readForm();
            const action = button.dataset.action;
            const listIndex = Number(button.dataset.listIndex);
            const memberIndex = Number(button.dataset.memberIndex);
            if (action === 'new-legislature') {
                configuration = emptyConfiguration();
                render();
                return;
            }
            if (action === 'add-list') configuration.lists.push(emptyList(`Liste ${configuration.lists.length + 1}`));
            if (action === 'remove-list') configuration.lists.splice(listIndex, 1);
            if (action === 'add-member') configuration.lists[listIndex].members.push(emptyMember(configuration.lists[listIndex].members.length + 1));
            if (action === 'remove-member') configuration.lists[listIndex].members.splice(memberIndex, 1);
            if (action === 'activate-legislature') {
                if (!confirm('Legislatur jetzt aktivieren und unveränderlich versiegeln?')) return;
                try {
                    const result = await repository.activate(configuration.id);
                    configuration = result.legislature;
                    render();
                    showNotice('Legislatur aktiviert und versiegelt.', 'success');
                } catch (error) {
                    showError(error, 'Legislatur konnte nicht aktiviert werden.');
                }
                return;
            }
            render();
        }

        function init() {
            const container = byId('legislature-editor');
            container.addEventListener('submit', handleSubmit);
            container.addEventListener('click', handleClick);
        }

        return { init, load, html, emptyConfiguration };
    }

    window.BRTop = window.BRTop || {};
    window.BRTop.legislatureEditor = { createController, html, emptyConfiguration };
})();
