(function() {
    const client = new window.OCA.BrTop.Shared.api.ApiClient({
        appId: 'brtop',
        errorMessage: (data, status) => data && data.ok === false && data.message ? data.message : `HTTP ${status}`
    });

    window.BRTop = window.BRTop || {};
    window.BRTop.api = {
        request: client.request.bind(client)
    };
})();
