(function() {
    const { Model, AgendaItem, GeneratedDocument } = window.BRTop.models;

    class Meeting extends Model {
        constructor(data = {}) {
            this.id = data.id ?? null;
            this.owner_uid = data.owner_uid || data.ownerUid || '';
            this.title = data.title || '';
            this.meeting_date = data.meeting_date || data.meetingDate || '';
            this.meeting_time = data.meeting_time || data.meetingTime || '';
            this.location = data.location || '';
            this.meeting_type = data.meeting_type || data.meetingType || 'custom';
            this.committee_code = data.committee_code || data.committeeCode || '';
            this.invitation_date = data.invitation_date || data.invitationDate || '';
            this.invitation_status = data.invitation_status || data.invitationStatus || 'not_created';
            this.status = data.status || 'draft';
            this.created_at = data.created_at || data.createdAt || '';
            this.tops = AgendaItem.get_all(data.tops || []);
            this.documents = GeneratedDocument.get_all(data.documents || []);
        }

        displayTitle() {
            return String(this.title || '').trim() || 'ohne Titel';
        }

        isRegularBrMeeting() {
            return this.meeting_type === 'regular_br';
        }

        toArray() {
            return {
                id: this.id,
                owner_uid: this.owner_uid,
                title: this.title,
                meeting_date: this.meeting_date,
                meeting_time: this.meeting_time,
                location: this.location,
                meeting_type: this.meeting_type,
                committee_code: this.committee_code,
                invitation_date: this.invitation_date,
                invitation_status: this.invitation_status,
                status: this.status,
                created_at: this.created_at,
                tops: this.tops.map(top => top.toArray()),
                documents: this.documents.map(document => document.toArray())
            };
        }
    }

    window.BRTop = window.BRTop || {};
    window.BRTop.models = window.BRTop.models || {};
    window.BRTop.models.Meeting = Meeting;
})();
