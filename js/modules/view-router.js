(function() {
    function createController({ byId, viewIds }) {
        const resetPosition = () => {
            const content = byId('content');
            if (content) {
                content.scrollTop = 0;
            }

            document.documentElement.scrollTop = 0;
            document.body.scrollTop = 0;
        };

        const show = (id) => {
            viewIds.forEach(viewId => {
                const view = byId(viewId);
                if (!view) {
                    return;
                }

                const active = viewId === id;
                view.classList.toggle('is-active', active);
                view.hidden = !active;
                view.setAttribute('aria-hidden', active ? 'false' : 'true');
            });

            resetPosition();
        };

        return {
            show
        };
    }

    window.BRTop = window.BRTop || {};
    window.BRTop.viewRouter = {
        createController
    };
})();
