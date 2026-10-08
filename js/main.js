(function() {
    let meetings = [];
    let currentSettings = {};
    let selectedMeetingId = null;
    let editingTopId = null;
    let capabilities = {};
    let documentOverlayTrigger = null;

    const { request: api } = window.BRTop.api;
    const {
        byId,
        fmtMeeting,
        showDocumentResult,
        showError,
        showNotice
    } = window.BRTop.ui;
    const { MeetingRepository, LegislatureRepository } = window.BRTop.repositories;
    const { sessionTableHtml } = window.BRTop.meetingList;
    const { createController: createMeetingDetailController } = window.BRTop.meetingDetail;
    const { protocolEditorHtml, createController: createProtocolEditorController } = window.BRTop.protocolEditor;
    const { createController: createAgendaEditorController } = window.BRTop.agendaEditor;
    const meetingRepository = new MeetingRepository(api);
    const legislatureRepository = new LegislatureRepository(api);

    const findMeeting = (id) => meetings.find(m => String(m.id) === String(id)) || null;

    const currentMeeting = () => selectedMeetingId ? findMeeting(selectedMeetingId) : null;

    const viewRouter = window.BRTop.viewRouter.createController({
        byId,
        viewIds: ['sessions-view', 'meeting-detail-view', 'protocol-view', 'legislature-view', 'absence-view']
    });
    const topForm = window.BRTop.topForm.createController(byId);
    const meetingDetail = createMeetingDetailController({
        byId,
        getMeeting: currentMeeting,
        getSettings: () => currentSettings,
        getEditingTopId: () => editingTopId
    });
    const protocolEditor = createProtocolEditorController({
        byId,
        repository: meetingRepository,
        getMeetingId: () => selectedMeetingId,
        loadState,
        render: renderProtocolEditor
    });
    const agendaEditor = createAgendaEditorController({
        byId,
        repository: meetingRepository,
        topForm,
        meetingDetail,
        getMeetingId: () => selectedMeetingId,
        setEditingTopId: (id) => {
            editingTopId = id;
        },
        clearEditingTopId: () => {
            editingTopId = null;
        },
        loadState,
        renderMeetingDetail
    });
    const legislatureEditor = window.BRTop.legislatureEditor.createController({
        byId,
        repository: legislatureRepository,
        showNotice,
        showError
    });
    const absenceReview = window.BRTop.absenceReview.createController({
        byId,
        repository: legislatureRepository,
        showNotice,
        showError
    });

    function renderSessionTable() {
        byId('state').innerHTML = sessionTableHtml(meetings);
    }

    async function loadState() {
        const data = await meetingRepository.state();
        meetings = data.meetings;
        currentSettings = data.settings || {};
        capabilities = data.capabilities || {};
        byId('manage-legislature').hidden = !capabilities.canManageLegislature;
        byId('review-absences').hidden = !capabilities.canManageLegislature;

        if (selectedMeetingId && !findMeeting(selectedMeetingId)) {
            selectedMeetingId = null;
        }

        renderSessionTable();
    }

    function renderMeetingDetail() {
        meetingDetail.render();
    }

    function openMeetingDetail(id) {
        selectedMeetingId = String(id);
        renderMeetingDetail();
        topForm.hide();
        viewRouter.show('meeting-detail-view');
    }

    function renderProtocolEditor() {
        const meeting = currentMeeting();
        const editor = byId('protocol-editor');

        if (!meeting) {
            byId('protocol-heading').textContent = 'Protokoll bearbeiten';
            editor.innerHTML = '<p>Die Sitzung wurde nicht gefunden.</p>';
            return;
        }

        byId('protocol-heading').textContent = `Protokoll: ${meeting.title || 'Sitzung'}`;

        const tops = meeting.tops || [];
        editor.innerHTML = protocolEditorHtml(tops);
        protocolEditor.afterRender();
    }

    function openProtocolEditor() {
        renderProtocolEditor();
        viewRouter.show('protocol-view');
    }

    async function createNewMeeting() {
        const result = await meetingRepository.createNextRegular();
        await loadState();
        openMeetingDetail(result.id);
        showNotice('Sitzung angelegt.', 'success');
    }

    async function deleteMeeting(id) {
        const meeting = findMeeting(id);
        const label = meeting ? fmtMeeting(meeting) : 'diese Sitzung';
        if (!confirm(`Sitzung "${label}" wirklich löschen?`)) {
            return;
        }

        await meetingRepository.deleteMeeting(id);
        if (String(selectedMeetingId) === String(id)) {
            selectedMeetingId = null;
        }
        await loadState();
        viewRouter.show('sessions-view');
        showNotice('Sitzung geloescht.', 'success');
    }

    async function generateInvitation() {
        const result = await meetingRepository.createInvitation(selectedMeetingId);
        await loadState();
        renderMeetingDetail();
        showDocumentResult(result, 'Ladung erzeugt.');
    }

    async function generateProtocolDocument() {
        await protocolEditor.saveDirty();

        const result = await meetingRepository.createProtocol(selectedMeetingId);
        await loadState();
        renderProtocolEditor();
        showDocumentResult(result, 'Protokolldokument erzeugt.');
    }

    function openDocumentOverlay(link) {
        const overlay = byId('document-overlay');
        const frame = byId('document-overlay-frame');
        const title = byId('document-overlay-title');
        if (!overlay || !frame || !title) {
            window.location.href = link.href;
            return;
        }

        title.textContent = link.getAttribute('data-document-title') || link.textContent || 'Dokument';
        frame.src = link.href;
        overlay.hidden = false;
        documentOverlayTrigger = link;
        const closeButton = byId('document-overlay-close');
        if (closeButton) {
            closeButton.focus();
        }
    }

    function closeDocumentOverlay() {
        const overlay = byId('document-overlay');
        const frame = byId('document-overlay-frame');
        if (frame) {
            frame.src = 'about:blank';
        }
        if (overlay) {
            overlay.hidden = true;
        }
        if (documentOverlayTrigger && typeof documentOverlayTrigger.focus === 'function') {
            documentOverlayTrigger.focus();
        }
        documentOverlayTrigger = null;
    }

    byId('new-meeting').addEventListener('click', async () => {
        try {
            await createNewMeeting();
        } catch (e) {
            showError(e, 'Sitzung konnte nicht angelegt werden.');
        }
    });

    byId('state').addEventListener('click', async (event) => {
        const button = event.target instanceof Element ? event.target.closest('button[data-action]') : null;
        if (!button) {
            return;
        }

        const id = button.getAttribute('data-id');
        const action = button.getAttribute('data-action');

        try {
            if (action === 'edit-meeting') {
                openMeetingDetail(id);
            } else if (action === 'delete-meeting') {
                await deleteMeeting(id);
            }
        } catch (e) {
            showError(e, 'Aktion konnte nicht ausgefuehrt werden.');
        }
    });

    byId('back-to-sessions').addEventListener('click', () => {
        viewRouter.show('sessions-view');
    });

    byId('manage-legislature').addEventListener('click', async () => {
        try {
            await legislatureEditor.load();
            viewRouter.show('legislature-view');
        } catch (e) {
            showError(e, 'Legislatur konnte nicht geladen werden.');
        }
    });

    byId('back-from-legislature').addEventListener('click', () => {
        viewRouter.show('sessions-view');
    });

    byId('review-absences').addEventListener('click', async () => {
        try {
            await absenceReview.load(selectedMeetingId);
            viewRouter.show('absence-view');
        } catch (e) {
            showError(e, 'Verhinderungen konnten nicht geladen werden.');
        }
    });

    byId('back-from-absences').addEventListener('click', () => {
        viewRouter.show('meeting-detail-view');
    });

    byId('detail-create-invitation').addEventListener('click', async () => {
        try {
            await generateInvitation();
        } catch (e) {
            showError(e, 'Ladung konnte nicht erzeugt werden.');
        }
    });

    byId('detail-edit-protocol').addEventListener('click', () => {
        openProtocolEditor();
    });

    const documentsArea = byId('meeting-documents');
    if (documentsArea) {
        documentsArea.addEventListener('click', (event) => {
            const link = event.target instanceof Element ? event.target.closest('a[data-document-overlay]') : null;
            if (!link) {
                return;
            }

            event.preventDefault();
            openDocumentOverlay(link);
        });
    }

    const documentOverlayClose = byId('document-overlay-close');
    const documentOverlay = byId('document-overlay');
    if (documentOverlayClose) {
        documentOverlayClose.addEventListener('click', closeDocumentOverlay);
    }
    if (documentOverlay) {
        documentOverlay.addEventListener('click', (event) => {
            if (event.target === documentOverlay) {
                closeDocumentOverlay();
            }
        });
        documentOverlay.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeDocumentOverlay();
                return;
            }
            if (event.key !== 'Tab') {
                return;
            }
            const focusable = [byId('document-overlay-close'), byId('document-overlay-frame')].filter(Boolean);
            if (focusable.length === 0) {
                return;
            }
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });
    }

    byId('back-to-detail').addEventListener('click', async () => {
        try {
            await protocolEditor.saveDirty();
            await loadState();
            openMeetingDetail(selectedMeetingId);
        } catch (e) {
            showError(e, 'Protokoll konnte nicht gespeichert werden.');
        }
    });

    byId('generate-protocol-document').addEventListener('click', async () => {
        try {
            await generateProtocolDocument();
        } catch (e) {
            showError(e, 'Protokoll konnte nicht erzeugt werden.');
        }
    });

    agendaEditor.init();
    legislatureEditor.init();
    absenceReview.init();
    protocolEditor.init();
    topForm.init();
    window.BRTop.persistentHorizontalScroll.bind(byId('brtop-app'));
    loadState().catch(e => showError(e, 'Sitzungen konnten nicht geladen werden.'));
})();
