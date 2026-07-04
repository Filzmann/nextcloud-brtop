(function() {
    const { Meeting } = window.BRTop.models;

    class MeetingRepository {
        constructor(request) {
            this.request = request;
        }

        async state() {
            const data = await this.request('/api/state');

            return {
                ...data,
                meetings: Meeting.get_all(data.meetings || []),
                settings: data.settings || {}
            };
        }

        createNextRegular() {
            return this.request('/api/meetings/next-regular', { method: 'POST' });
        }

        deleteMeeting(meetingId) {
            return this.request(`/api/meetings/${meetingId}/delete`, { method: 'POST' });
        }

        createInvitation(meetingId) {
            return this.request(`/api/meetings/${meetingId}/invitation`, { method: 'POST' });
        }

        createProtocol(meetingId) {
            return this.request(`/api/meetings/${meetingId}/protocol`, { method: 'POST' });
        }

        addTop(meetingId, payload) {
            return this.request(`/api/meetings/${meetingId}/tops`, {
                method: 'POST',
                body: JSON.stringify(payload)
            });
        }

        moveTop(meetingId, topId, direction) {
            return this.request(`/api/meetings/${meetingId}/tops/${topId}/move`, {
                method: 'POST',
                body: JSON.stringify({ direction })
            });
        }

        changeTopDepth(meetingId, topId, direction) {
            return this.request(`/api/meetings/${meetingId}/tops/${topId}/depth`, {
                method: 'POST',
                body: JSON.stringify({ direction })
            });
        }

        saveTopSubject(meetingId, topId, subject) {
            return this.request(`/api/meetings/${meetingId}/tops/${topId}/subject`, {
                method: 'POST',
                body: JSON.stringify({ subject })
            });
        }

        deleteTop(meetingId, topId) {
            return this.request(`/api/meetings/${meetingId}/tops/${topId}/delete`, { method: 'POST' });
        }

        addProtocolBlock(meetingId, topId) {
            return this.request(`/api/meetings/${meetingId}/tops/${topId}/protocol-blocks`, {
                method: 'POST',
                body: JSON.stringify({ blockType: 'text', content: '' })
            });
        }

        saveProtocolBlock(meetingId, topId, blockId, content) {
            return this.request(`/api/meetings/${meetingId}/tops/${topId}/protocol-blocks/${blockId}`, {
                method: 'POST',
                body: JSON.stringify({ content })
            });
        }
    }

    window.BRTop = window.BRTop || {};
    window.BRTop.repositories = window.BRTop.repositories || {};
    window.BRTop.repositories.MeetingRepository = MeetingRepository;
})();
