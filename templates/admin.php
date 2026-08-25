<?php
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('brtop', 'modules/api');
\OCP\Util::addScript('brtop', 'admin');
?>
<section id="brtop-admin" class="section" aria-labelledby="brtop-admin-heading">
    <h2 id="brtop-admin-heading">BR TOP</h2>
    <h3 id="brtop-full-access-heading">Zeitlich begrenzter Admin-Vollzugriff</h3>
    <p>Native Nextcloud-Administration erteilt keinen automatischen Zugriff auf Sitzungs- und Gremiendaten. Eine Freigabe gilt nur für das angegebene Administrationskonto. Maximal 24 Stunden sind zulässig.</p>
    <form id="brtop-full-access-form"><label>Admin-Benutzerkennung <input name="targetUid" required maxlength="64" autocomplete="off"></label><label>Dauer <select name="durationMinutes" required><option value="60">1 Stunde</option><option value="240">4 Stunden</option><option value="480">8 Stunden</option><option value="1440">24 Stunden</option></select></label><label><input id="brtop-full-access-enabled" name="enabled" type="checkbox" required> Vollzugriff für diesen Zeitraum aktivieren</label><button type="submit" class="primary">Freigabe aktivieren</button></form>
    <p id="brtop-full-access-status" role="status" aria-live="polite"></p>
    <table><caption>Protokollierte Admin-Vollzugriffszeiträume</caption><thead><tr><th>Ziel-Admin</th><th>Freigegeben von</th><th>Von</th><th>Geplant bis</th><th>Tatsächlich bis / Status</th><th>Aktion</th></tr></thead><tbody id="brtop-full-access-history"><tr><td colspan="6">Freigaben werden geladen.</td></tr></tbody></table>
</section>
