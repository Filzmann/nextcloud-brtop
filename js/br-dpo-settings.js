(function() {
    const translate = typeof t === 'function' ? t : (_app, text, params) => Object.entries(params || {}).reduce((s, [k, v]) => s.split('{' + k + '}').join(String(v)), text)
    'use strict';
    const request = window.BRTop?.api?.request;
    const form = document.getElementById('brtop-br-dpo-form');
    const select = document.getElementById('brtop-br-dpo-member-select');
    const status = document.getElementById('brtop-br-dpo-status');
    if (!request || !form || !select || !status) return;
    const show = message => { status.textContent = message; };
    const load = async () => {
        const state = await request('/api/permissions/br-dpo');
        select.replaceChildren();
        (state.members || []).forEach(member => {
            const option = document.createElement('option');
            option.value = member.uid;
            option.textContent = `${member.displayName} (${member.uid})`;
            option.selected = member.assigned === true;
            select.append(option);
        });
    };
    form.addEventListener('submit', async event => {
        event.preventDefault();
        try {
            const selected = Array.from(select.selectedOptions, option => option.value);
            await request('/api/permissions/br-dpo', {
                method: 'POST',
                body: JSON.stringify({ memberUidsJson: JSON.stringify(selected) }),
            });
            show(translate('brtop', translate('brtop', 'Die BR-Datenschutzberechtigung wurde gespeichert.')));
            await load();
        } catch (error) {
            show(error.message || translate('brtop', translate('brtop', 'Die BR-Datenschutzberechtigung konnte nicht gespeichert werden.')));
        }
    });
    load().catch(error => show(error.message || translate('brtop', translate('brtop', 'Die BR-Mitglieder konnten nicht geladen werden.'))));
})();
