(function() {
    function bind(appRoot) {
        appRoot.__persistentHorizontalScrollCleanup?.();

        const track = appRoot.querySelector('[data-persistent-horizontal-scroll-track]');
        const spacer = track?.querySelector('[data-persistent-horizontal-scroll-spacer]');
        if (!track || !spacer) {
            return () => {};
        }

        let target = null;
        let syncing = false;
        const visibleTarget = () => Array.from(appRoot.querySelectorAll('[data-persistent-horizontal-scroll]')).find((candidate) => {
            if (candidate.scrollWidth <= candidate.clientWidth || typeof candidate.getBoundingClientRect !== 'function') return false;
            const bounds = candidate.getBoundingClientRect();
            const rootBounds = appRoot.getBoundingClientRect?.();
            return bounds.bottom > (rootBounds?.top ?? 0) && bounds.top < (rootBounds?.bottom ?? window.innerHeight);
        }) || null;
        const syncFromTarget = () => {
            if (!syncing && target) {
                syncing = true;
                track.scrollLeft = target.scrollLeft;
                syncing = false;
            }
        };
        const syncFromTrack = () => {
            if (!syncing && target) {
                syncing = true;
                target.scrollLeft = track.scrollLeft;
                syncing = false;
            }
        };
        const refresh = () => {
            const nextTarget = visibleTarget();
            if (nextTarget !== target) {
                target?.removeEventListener('scroll', syncFromTarget);
                target = nextTarget;
                target?.addEventListener('scroll', syncFromTarget);
            }
            track.hidden = !target;
            track.classList?.toggle('brtop-persistent-scroll-track-active', Boolean(target));
            if (!target) return;
            spacer.style.width = `${target.scrollWidth}px`;
            const bounds = target.getBoundingClientRect();
            const rootBounds = appRoot.getBoundingClientRect?.();
            track.style.left = `${Math.max(bounds.left, rootBounds?.left ?? 0)}px`;
            track.style.width = `${Math.min(bounds.width, rootBounds?.width ?? bounds.width)}px`;
            track.style.bottom = `${Math.max(0, window.innerHeight - (rootBounds?.bottom ?? window.innerHeight))}px`;
            syncFromTarget();
        };

        track.addEventListener('scroll', syncFromTrack);
        appRoot.addEventListener('scroll', refresh);
        window.addEventListener?.('resize', refresh);
        const ResizeObserverClass = window.ResizeObserver;
        const resizeObserver = ResizeObserverClass ? new ResizeObserverClass(refresh) : null;
        resizeObserver?.observe(appRoot);
        const mutationObserver = window.MutationObserver ? new window.MutationObserver(refresh) : null;
        mutationObserver?.observe(appRoot, { childList: true, subtree: true });
        refresh();

        const cleanup = () => {
            target?.removeEventListener('scroll', syncFromTarget);
            track.removeEventListener('scroll', syncFromTrack);
            appRoot.removeEventListener('scroll', refresh);
            window.removeEventListener?.('resize', refresh);
            resizeObserver?.disconnect();
            mutationObserver?.disconnect();
            if (appRoot.__persistentHorizontalScrollCleanup === cleanup) delete appRoot.__persistentHorizontalScrollCleanup;
        };
        appRoot.__persistentHorizontalScrollCleanup = cleanup;
        return cleanup;
    }

    window.BRTop = window.BRTop || {};
    window.BRTop.persistentHorizontalScroll = { bind };
})();
