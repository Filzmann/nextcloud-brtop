(function() {
    const {
        esc,
        fmtDate,
        fmtTime
    } = window.BRTop.ui;
    const { agendaListHtml, documentsHtml } = window.BRTop.agendaList;

    function createController({ byId, getMeeting, getSettings, getEditingTopId }) {
        const meetingTypeLabel = (type) => {
            const settings = getSettings() || {};
            const found = (settings.meetingTypes || []).find(item => item.value === type);

            if (found) {
                return found.label;
            }

            return type || '';
        };

        const metaText = (meeting) => [
            fmtDate(meeting.meeting_date),
            fmtTime(meeting.meeting_time),
            meeting.location || '',
            meetingTypeLabel(meeting.meeting_type),
            meeting.committee_code ? `Ausschuss: ${meeting.committee_code}` : '',
            meeting.invitation_date ? `Ladung: ${fmtDate(meeting.invitation_date)}` : '',
            meeting.invitation_status ? `Status: ${meeting.invitation_status}` : ''
        ].filter(Boolean).join(' · ');

        const snapshotRowHtml = (recipient) => {
            const invitationType = recipient.invitation_type || 'initial';
            const status = invitationType === 'absent'
                ? 'Nicht geladen – Verhinderung bestätigt'
                : invitationType === 'replacement' ? 'Als Ersatzmitglied geladen' : 'Geladen';
            const list = [recipient.list_name || '', recipient.list_rank ? `Rang ${recipient.list_rank}` : '']
                .filter(Boolean).join(', ');
            const replacement = recipient.replacement_for_name
                ? `für ${recipient.replacement_for_name}`
                : '';
            return `<tr>
                <td>${esc(recipient.display_name || recipient.user_uid || '')}</td>
                <td>${esc(status)}</td>
                <td>${esc(list)}</td>
                <td>${esc(replacement)}</td>
            </tr>`;
        };

        const invitationStatusHtml = (meeting) => {
            const recipients = meeting.invitation_recipients || [];
            if (recipients.length === 0) {
                return `
                    <section class="brtop-invitation-status">
                        <h3>Ladungsstatus</h3>
                        <p class="brtop-meta">Noch keine Ladungsliste erzeugt.</p>
                    </section>
                `;
            }

            const loaded = recipients.filter(recipient => (recipient.invitation_type || 'initial') !== 'absent');
            const replacements = recipients.filter(recipient => (recipient.invitation_type || '') === 'replacement');
            const absent = recipients.filter(recipient => (recipient.invitation_type || '') === 'absent');

            return `
                <section class="brtop-invitation-status">
                    <h3>Ladungsstatus</h3>
                    <p class="brtop-meta">Geladen: ${loaded.length}, davon Ersatzmitglieder: ${replacements.length}; bestätigte Verhinderungen: ${absent.length}.</p>
                    <div class="brtop-table-wrap" data-persistent-horizontal-scroll><table class="brtop-session-table">
                        <caption>Unveränderlicher Empfängersnapshot der Einladung</caption>
                        <thead><tr><th scope="col">Name</th><th scope="col">Status</th><th scope="col">Liste/Rang</th><th scope="col">Nachrückung</th></tr></thead>
                        <tbody>${recipients.map(snapshotRowHtml).join('')}</tbody>
                    </table></div>
                </section>
            `;
        };

        const editingInput = (topId) => {
            const content = byId('meeting-detail-content');
            if (!content) {
                return null;
            }

            return Array.from(content.querySelectorAll('[data-top-edit-input]'))
                .find(input => String(input.getAttribute('data-top-id')) === String(topId)) || null;
        };

        const focusEditingInput = () => {
            const editingTopId = getEditingTopId();
            if (editingTopId === null || editingTopId === undefined || editingTopId === '') {
                return;
            }

            const input = editingInput(editingTopId);
            if (input) {
                input.focus();
                input.select();
            }
        };

        const render = () => {
            const meeting = getMeeting();
            const heading = byId('meeting-detail-heading');
            const content = byId('meeting-detail-content');
            const documents = byId('meeting-documents');

            if (!heading || !content || !documents) {
                return;
            }

            if (!meeting) {
                heading.textContent = 'Sitzung';
                content.innerHTML = '<p>Die Sitzung wurde nicht gefunden.</p>';
                documents.innerHTML = '';
                return;
            }

            const meta = metaText(meeting);

            heading.textContent = meeting.title || 'Sitzung';
            content.innerHTML = `
                ${meta ? `<p class="brtop-meta">${esc(meta)}</p>` : ''}
                ${invitationStatusHtml(meeting)}
                <h3>TOP-Liste</h3>
                ${agendaListHtml(meeting.tops || [], getEditingTopId())}
            `;
            documents.innerHTML = documentsHtml(meeting.documents || []);

            focusEditingInput();
        };

        return {
            render,
            editingInput
        };
    }

    window.BRTop = window.BRTop || {};
    window.BRTop.meetingDetail = {
        createController
    };
})();
