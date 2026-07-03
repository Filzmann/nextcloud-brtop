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

            if (!heading || !content) {
                return;
            }

            if (!meeting) {
                heading.textContent = 'Sitzung';
                content.innerHTML = '<p>Die Sitzung wurde nicht gefunden.</p>';
                return;
            }

            const meta = metaText(meeting);

            heading.textContent = meeting.title || 'Sitzung';
            content.innerHTML = `
                ${meta ? `<p class="brtop-meta">${esc(meta)}</p>` : ''}
                <h3>TOP-Liste</h3>
                ${agendaListHtml(meeting.tops || [], getEditingTopId())}
                ${documentsHtml(meeting.documents || [])}
            `;

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
