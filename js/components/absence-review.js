(function() {
    const { esc } = window.BRTop.ui;

    function html(state) {
        if (!state) return '<p>Keine Verhinderungsdaten geladen.</p>';
        const locked = Boolean(state.snapshot_locked);
        const rows = (state.members || []).map(member => {
            const checked = ['suggested', 'confirmed'].includes(member.absence_status) ? 'checked' : '';
            const id = `absence-member-${Number(member.id)}`;
            const role = member.member_role === 'replacement' ? 'Ersatzmitglied' : 'festes Mitglied';
            const suggestion = member.absence_status === 'suggested' ? ' – Kalendervorschlag' : '';
            return `<tr>
                <td><input id="${id}" type="checkbox" name="memberIds" value="${Number(member.id)}" ${checked} ${locked ? 'disabled' : ''}></td>
                <td><label for="${id}">${esc(member.display_name)}</label></td>
                <td>${esc(member.list_name || '')}</td>
                <td>${Number(member.list_rank || 0)}</td>
                <td>${esc(role + suggestion)}</td>
            </tr>`;
        }).join('');
        return `<form id="absence-review-form">
            <p>Kalenderdaten sind nur eine Vorbelegung. Erst diese administrative Bestätigung wirkt auf die Nachladung.</p>
            <p><strong>Sitzungsdatum:</strong> ${esc(state.meeting_date)}</p>
            ${state.reviewed ? '<p>Die Verhinderungsliste wurde bereits administrativ bestätigt.</p>' : ''}
            ${locked ? '<p><strong>Versiegelt:</strong> Der Einladungssnapshot ist bereits vorhanden und kann nicht verändert werden.</p>' : ''}
            <div class="brtop-table-wrap" data-persistent-horizontal-scroll><table class="brtop-session-table">
                <caption>Verhinderungen und nicht verfügbare Ersatzmitglieder</caption>
                <thead><tr><th scope="col">Verhindert</th><th scope="col">Name</th><th scope="col">Liste</th><th scope="col">Rang</th><th scope="col">Rolle/Quelle</th></tr></thead>
                <tbody>${rows || '<tr><td colspan="5">Keine Mitglieder in der Legislatur.</td></tr>'}</tbody>
            </table></div>
            ${locked ? '' : '<button class="primary" type="submit">Verhinderungen verbindlich bestätigen</button>'}
        </form>`;
    }

    function createController({ byId, repository, showNotice, showError }) {
        let meetingId = null;
        let state = null;

        function render() { byId('absence-review').innerHTML = html(state); }

        async function load(id) {
            meetingId = id;
            const result = await repository.absences(id);
            state = result.absenceState;
            render();
        }

        async function submit(event) {
            event.preventDefault();
            const ids = Array.from(byId('absence-review-form').querySelectorAll('input[name="memberIds"]:checked'))
                .map(input => Number(input.value));
            try {
                const result = await repository.saveAbsences(meetingId, ids);
                state = result.absenceState;
                render();
                showNotice('Verhinderungen verbindlich bestätigt.', 'success');
            } catch (error) {
                showError(error, 'Verhinderungen konnten nicht bestätigt werden.');
            }
        }

        function init() {
            byId('absence-review').addEventListener('submit', submit);
        }

        return { init, load, html };
    }

    window.BRTop = window.BRTop || {};
    window.BRTop.absenceReview = { createController, html };
})();
