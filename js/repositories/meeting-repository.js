(function() {
    const { Repository } = window.LocalBase.repositories;
    const { Meeting } = window.BRTop.models;

    class MeetingRepository extends Repository {
        async state() {
            const data = await this.request('/api/state');

            return {
                ...data,
                meetings: Meeting.get_all(data.meetings || []),
                settings: data.settings || {}
            };
        }

        createNextRegular() {
            return this.post('/api/meetings/next-regular');
        }

        deleteMeeting(meetingId) {
            return this.post(`/api/meetings/${meetingId}/delete`);
        }

        createInvitation(meetingId) {
            return this.post(`/api/meetings/${meetingId}/invitation`);
        }

        createProtocol(meetingId) {
            return this.post(`/api/meetings/${meetingId}/protocol`);
        }

        addTop(meetingId, payload) {
            return this.post(`/api/meetings/${meetingId}/tops`, payload);
        }

        moveTop(meetingId, topId, direction) {
            return this.post(`/api/meetings/${meetingId}/tops/${topId}/move`, { direction });
        }

        changeTopDepth(meetingId, topId, direction) {
            return this.post(`/api/meetings/${meetingId}/tops/${topId}/depth`, { direction });
        }

        saveTopSubject(meetingId, topId, subject) {
            return this.post(`/api/meetings/${meetingId}/tops/${topId}/subject`, { subject });
        }

        deleteTop(meetingId, topId) {
            return this.post(`/api/meetings/${meetingId}/tops/${topId}/delete`);
        }

        addProtocolBlock(meetingId, topId) {
            return this.post(`/api/meetings/${meetingId}/tops/${topId}/protocol-blocks`, { blockType: 'text', content: '' });
        }

        saveProtocolBlock(meetingId, topId, blockId, content) {
            return this.post(`/api/meetings/${meetingId}/tops/${topId}/protocol-blocks/${blockId}`, { content });
        }
    }

    window.BRTop = window.BRTop || {};
    window.BRTop.repositories = window.BRTop.repositories || {};
    window.BRTop.repositories.MeetingRepository = MeetingRepository;
})();
