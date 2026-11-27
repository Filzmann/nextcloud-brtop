(function () {
    'use strict';

    const shared = window.OCA.BrTop?.Shared?.ui;
    const request = window.BRTop?.api?.request;
    if (!shared?.AdminAccessOverlay || !request) return;

    new shared.AdminAccessOverlay({
        overlayId: 'brtop-admin-access-overlay',
        triggerId: 'brtop-admin-access-warning',
        closeId: 'brtop-admin-access-overlay-close',
        formId: 'brtop-full-access-form',
        historyId: 'brtop-full-access-history',
        statusId: 'brtop-full-access-status',
        api: {
            getState: () => request('/api/admin/full-access'),
            activate: (targetUid, durationMinutes) => request('/api/admin/full-access', {
                method: 'POST',
                body: JSON.stringify({ targetUid, durationMinutes }),
            }),
            revoke: (targetUid) => request(`/api/admin/full-access/${encodeURIComponent(targetUid)}`, { method: 'DELETE' }),
        },
    });
})();
