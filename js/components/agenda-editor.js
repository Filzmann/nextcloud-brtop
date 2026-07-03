(function() {
    function createController({
        byId,
        repository,
        topForm,
        meetingDetail,
        getMeetingId,
        setEditingTopId,
        clearEditingTopId,
        loadState,
        renderMeetingDetail
    }) {
        const saveTopSubject = async (topId) => {
            const input = meetingDetail.editingInput(topId);
            if (!input) {
                return;
            }

            const subject = input.value.trim();
            if (subject === '') {
                alert('Der TOP-Betreff darf nicht leer sein.');
                input.focus();
                return;
            }

            await repository.saveTopSubject(getMeetingId(), topId, subject);
            clearEditingTopId();
            await loadState();
            renderMeetingDetail();
        };

        const moveTop = async (topId, direction) => {
            await repository.moveTop(getMeetingId(), topId, direction);
            await loadState();
            renderMeetingDetail();
        };

        const changeTopDepth = async (topId, direction) => {
            await repository.changeTopDepth(getMeetingId(), topId, direction);
            await loadState();
            renderMeetingDetail();
        };

        const deleteTop = async (topId, label) => {
            if (!confirm(`TOP "${label}" wirklich löschen? Untergeordnete TOPs werden ebenfalls gelöscht.`)) {
                return;
            }

            await repository.deleteTop(getMeetingId(), topId);
            await loadState();
            renderMeetingDetail();
        };

        const handleTopClick = async (event) => {
            const button = event.target instanceof Element ? event.target.closest('button[data-action]') : null;
            if (!button) {
                return;
            }

            const action = button.getAttribute('data-action');
            const topId = button.getAttribute('data-top-id');
            const direction = button.getAttribute('data-direction') || '';

            try {
                if (action === 'edit-top') {
                    setEditingTopId(topId);
                    renderMeetingDetail();
                } else if (action === 'save-top-title') {
                    await saveTopSubject(topId);
                } else if (action === 'move-top') {
                    await moveTop(topId, direction);
                } else if (action === 'depth-top') {
                    await changeTopDepth(topId, direction);
                } else if (action === 'delete-top') {
                    await deleteTop(topId, button.getAttribute('data-label') || 'diesen TOP');
                }
            } catch (e) {
                alert('Fehler beim Bearbeiten der TOP-Liste:\n' + e.message);
            }
        };

        const handleTopKeydown = async (event) => {
            const input = event.target;
            if (!(input instanceof HTMLInputElement) || !input.matches('[data-top-edit-input]')) {
                return;
            }

            if (event.key === 'Escape') {
                clearEditingTopId();
                renderMeetingDetail();
                return;
            }

            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();

            try {
                await saveTopSubject(input.getAttribute('data-top-id'));
            } catch (e) {
                alert('Fehler beim Speichern des TOP:\n' + e.message);
            }
        };

        const addTop = async () => {
            try {
                const meetingId = getMeetingId();
                if (!meetingId) {
                    alert('Bitte zuerst eine Sitzung öffnen.');
                    return;
                }

                const payload = topForm.payload();

                await repository.addTop(meetingId, payload);

                topForm.clear();
                topForm.hide();
                await loadState();
                renderMeetingDetail();
            } catch (e) {
                alert('Fehler beim Speichern des TOP:\n' + e.message);
            }
        };

        const init = () => {
            const content = byId('meeting-detail-content');
            const addButton = byId('add-top');

            if (content) {
                content.addEventListener('click', handleTopClick);
                content.addEventListener('keydown', handleTopKeydown);
            }

            if (addButton) {
                addButton.addEventListener('click', addTop);
            }
        };

        return {
            init
        };
    }

    window.BRTop = window.BRTop || {};
    window.BRTop.agendaEditor = {
        createController
    };
})();
