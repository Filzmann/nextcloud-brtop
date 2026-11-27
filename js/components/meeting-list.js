(function() {
    const translate = typeof t === 'function' ? t : (_app, text, params) => Object.entries(params || {}).reduce((s, [k, v]) => s.split('{' + k + '}').join(String(v)), text)
    const {
        esc,
        fmtDate,
        fmtTime,
        buttonPresetHtml
    } = window.BRTop.ui;

    function sessionTableHtml(meetings) {
        if (meetings.length === 0) {
            return '<p>' + translate('brtop', translate('brtop', 'Noch keine Sitzung vorhanden.')) + '</p>';
        }

        const rows = meetings.map(meeting => `
            <tr>
                <td>${esc(fmtDate(meeting.meeting_date))}</td>
                <td>${esc(fmtTime(meeting.meeting_time))}</td>
                <td>${esc(meeting.title || translate('brtop', translate('brtop', 'ohne Titel')))}</td>
                <td class="brtop-table-actions">
                    ${buttonPresetHtml('editMeeting', { 'data-id': meeting.id })}
                    ${buttonPresetHtml('deleteMeeting', { 'data-id': meeting.id })}
                </td>
            </tr>
        `).join('');

        return `
            <div class="brtop-table-wrap" data-persistent-horizontal-scroll>
                <table class="brtop-session-table">
                    <thead>
                        <tr>
                            <th>${translate('brtop', translate('brtop', 'Datum'))}</th>
                            <th>${translate('brtop', translate('brtop', 'Uhrzeit'))}</th>
                            <th>${translate('brtop', translate('brtop', 'Titel'))}</th>
                            <th>${translate('brtop', translate('brtop', 'Aktionen'))}</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    window.BRTop = window.BRTop || {};
    window.BRTop.meetingList = { sessionTableHtml };
})();
