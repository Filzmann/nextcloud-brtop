(function() {
    const { Repository } = window.LocalBase.repositories;

    class LegislatureRepository extends Repository {
        load() {
            return this.request('/api/legislature');
        }

        save(configuration) {
            return this.post('/api/legislature', {
                configurationJson: JSON.stringify(configuration)
            });
        }

        activate(legislatureId) {
            return this.post(`/api/legislature/${legislatureId}/activate`);
        }

        absences(meetingId) {
            return this.request(`/api/meetings/${meetingId}/absences`);
        }

        saveAbsences(meetingId, memberIds) {
            return this.post(`/api/meetings/${meetingId}/absences`, {
                memberIdsJson: JSON.stringify(memberIds)
            });
        }
    }

    window.BRTop = window.BRTop || {};
    window.BRTop.repositories = window.BRTop.repositories || {};
    window.BRTop.repositories.LegislatureRepository = LegislatureRepository;
})();
