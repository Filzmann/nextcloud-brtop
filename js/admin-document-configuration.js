(function() {
    const translate = typeof t === 'function' ? t : (_app, text, params) => Object.entries(params || {}).reduce((s, [k, v]) => s.split('{' + k + '}').join(String(v)), text)
    const endpoint = '/api/admin/document-configuration';
    const byId = id => document.getElementById(id);
    const request = (path, options) => window.BRTop?.api?.request(path, options);
    let selected = null;
    let templates = [];
    const fields = ['councilName', 'councilAddress', 'issuePlace', 'committeeCodesJson', 'invitationSalutation', 'invitationIntroduction', 'invitationClosing', 'invitationSender'];

    function show(message) { byId('brtop-document-configuration-status').textContent = message; }
    function encode(value) { return String(value).replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]); }
    function organizationPayload() { const form = byId('brtop-document-organization-form'); return Object.fromEntries(fields.map(name => [name, form.querySelector(`[name="${name}"]`).value])); }
    function templateLabel(key) { return ({ invitation_email: 'Einladungs-E-Mail', invitation_markdown: 'Einladung (Markdown)', protocol_markdown: translate('brtop', translate('brtop', 'Protokoll (Markdown)')), resolution_markdown: 'Beschluss (Markdown)', protocol_odt: translate('brtop', translate('brtop', 'Protokoll (ODT)')) })[key] || key; }
    function metadataLabel(item) { return `${templateLabel(item.templateKey)} · Version ${item.version === 0 ? 'eingebaut' : item.version} · ${item.status === 'approved' ? 'freigegeben' : item.status === 'built_in' ? 'eingebaut' : translate('brtop', translate('brtop', 'Entwurf'))}`; }
    function renderTemplates() { byId('brtop-document-template-list').innerHTML = templates.map(item => `<button type="button" role="listitem" class="brtop-document-template-list__item" data-template-key="${encode(item.templateKey)}" data-version="${item.version}" aria-controls="brtop-document-template-editor">${encode(metadataLabel(item))}</button>`).join('') || '<p>' + translate('brtop', translate('brtop', 'Noch keine Vorlagen verfügbar.')) + '</p>'; }
    function setOrganization(organization) {
        const form = byId('brtop-document-organization-form');
        Object.entries(organization || {}).forEach(([name, value]) => {
            const field = form.querySelector(`[name="${name === 'committeeCodes' ? 'committeeCodesJson' : name}"]`);
            if (field) field.value = name === 'committeeCodes' ? JSON.stringify(value) : value ?? '';
        });
    }
    function base64(bytes) { let binary = ''; new Uint8Array(bytes).forEach(byte => { binary += String.fromCharCode(byte); }); return btoa(binary); }
    async function contentPayload() {
        if (!selected) throw new Error(translate('brtop', translate('brtop', 'Bitte wählen Sie zuerst eine Vorlage aus.')));
        if (selected.format !== 'odt') return { content: byId('brtop-document-template-content').value, encoding: 'plain' };
        const file = byId('brtop-document-template-file').files[0];
        if (!file) throw new Error(translate('brtop', translate('brtop', 'Bitte wählen Sie eine ODT-Datei aus.')));
        return { content: base64(await file.arrayBuffer()), encoding: 'base64' };
    }
    function showEditor(template) {
        selected = template;
        const isOdt = template.format === 'odt';
        byId('brtop-document-template-editor').hidden = false;
        byId('brtop-document-template-editor-heading').textContent = templateLabel(template.templateKey);
        byId('brtop-document-template-meta').textContent = metadataLabel(template);
        byId('brtop-document-template-content-label').hidden = isOdt;
        byId('brtop-document-template-file-label').hidden = !isOdt;
        byId('brtop-document-template-content').value = isOdt ? '' : (template.content || '');
        byId('brtop-document-template-approve').disabled = template.version === 0 || template.status === 'approved';
        byId('brtop-document-template-preview').textContent = '';
        byId('brtop-document-template-editor-heading').focus?.();
    }
    async function selectTemplate(button) { showEditor((await request(`${endpoint}/templates/${encodeURIComponent(button.dataset.templateKey)}/${encodeURIComponent(button.dataset.version)}`)).template); }
    async function load() { const state = await request(endpoint); setOrganization(state.organization); templates = state.templates || []; renderTemplates(); }
    async function saveOrganization(event) { event.preventDefault(); setOrganization((await request(`${endpoint}/organization`, { method: 'POST', body: JSON.stringify(organizationPayload()) })).organization); show(translate('brtop', translate('brtop', 'Die Organisationsstammdaten wurden gespeichert.'))); }
    async function preview() { const result = await request(`${endpoint}/templates/${encodeURIComponent(selected.templateKey)}/preview`, { method: 'POST', body: JSON.stringify(await contentPayload()) }); byId('brtop-document-template-preview').textContent = result.preview.format === 'odt' ? 'Die ODT-Vorlage ist gültig und kann verwendet werden.' : result.preview.content; show(translate('brtop', translate('brtop', 'Die Vorschau wurde geprüft.'))); }
    async function saveDraft() { const result = await request(`${endpoint}/templates/${encodeURIComponent(selected.templateKey)}/draft`, { method: 'POST', body: JSON.stringify(await contentPayload()) }); templates.push(result.template); renderTemplates(); showEditor(result.template); show(translate('brtop', translate('brtop', 'Der Vorlagenentwurf wurde gespeichert.'))); }
    async function approve() { const result = await request(`${endpoint}/templates/${encodeURIComponent(selected.templateKey)}/${selected.version}/approve`, { method: 'POST', body: JSON.stringify({}) }); templates = templates.map(item => item.templateKey === result.template.templateKey && item.version === result.template.version ? result.template : item); renderTemplates(); showEditor(result.template); show(translate('brtop', translate('brtop', 'Die Vorlage wurde freigegeben.'))); }
    function report(error, fallback) { show(error?.message || fallback); }
    function init() {
        const form = byId('brtop-document-organization-form'); const list = byId('brtop-document-template-list');
        if (!request || !form || !list) return;
        form.addEventListener('submit', event => saveOrganization(event).catch(error => report(error, translate('brtop', translate('brtop', 'Die Organisationsstammdaten konnten nicht gespeichert werden.')))));
        list.addEventListener('click', event => { const button = event.target.closest('button[data-template-key]'); if (button) selectTemplate(button).catch(error => report(error, translate('brtop', translate('brtop', 'Die Vorlage konnte nicht geladen werden.')))); });
        byId('brtop-document-template-preview-button').addEventListener('click', () => preview().catch(error => report(error, translate('brtop', translate('brtop', 'Die Vorschau konnte nicht erstellt werden.')))));
        byId('brtop-document-template-save-draft').addEventListener('click', () => saveDraft().catch(error => report(error, translate('brtop', translate('brtop', 'Der Entwurf konnte nicht gespeichert werden.')))));
        byId('brtop-document-template-approve').addEventListener('click', () => approve().catch(error => report(error, translate('brtop', translate('brtop', 'Die Vorlage konnte nicht freigegeben werden.')))));
        load().catch(error => report(error, translate('brtop', translate('brtop', 'Die Dokumentkonfiguration konnte nicht geladen werden.'))));
    }
    init();
})();
