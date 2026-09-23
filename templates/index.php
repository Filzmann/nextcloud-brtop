<?php
use OCA\BrTop\View\UiComponents;

script('localbase', 'api/api-client');
script('brtop', 'modules/api');
script('orgsuite', 'suite-navigation');
script('brtop', 'admin-access');
if ($_['hasBrTopAccess'] ?? false) {
    script('localbase', 'ui/ui');
    script('brtop', 'modules/ui');
    script('brtop', 'modules/view-router');
    script('localbase', 'models/model');
    script('brtop', 'models/protocol-block');
    script('brtop', 'models/agenda-item');
    script('brtop', 'models/generated-document');
    script('brtop', 'models/meeting');
    script('localbase', 'repositories/repository');
    script('brtop', 'repositories/meeting-repository');
    script('brtop', 'repositories/legislature-repository');
    script('brtop', 'components/meeting-list');
    script('brtop', 'components/agenda-list');
    script('brtop', 'components/meeting-detail');
    script('brtop', 'components/protocol-editor');
    script('brtop', 'components/top-form');
    script('brtop', 'components/agenda-editor');
    script('brtop', 'components/legislature-editor');
    script('brtop', 'components/absence-review');
    script('brtop', 'main');
}
style('brtop', 'style');
style('orgsuite', 'suite-navigation');
?>

<div id="brtop-app">
    <div class="orgsuite-host" data-orgsuite data-suite="br" data-current-app="brtop"></div>
    <h1>BR TOP- und Sitzungsverwaltung</h1>
    <div id="brtop-notice" class="brtop-notice" role="status" aria-live="polite" hidden></div>

    <?php if ($_['showMissingAdminGrant'] ?? false): ?>
        <aside class="brtop-access-notice" role="status">
            <strong>Für dieses Administrationskonto ist kein zeitlich begrenzter fachlicher Vollzugriff aktiv.</strong>
            <?php if ($_['showAdminAccessLink'] ?? false): ?><a href="#brtop-full-access-heading">Freigabesteuerung öffnen</a><?php endif; ?>
        </aside>
    <?php endif; ?>

    <?php if ($_['canManageAdminAccess'] ?? false): ?>
        <section id="brtop-full-access" class="brtop-card brtop-access-card" aria-labelledby="brtop-full-access-heading">
            <h2 id="brtop-full-access-heading" tabindex="-1">Zeitlich begrenzter Admin-Vollzugriff</h2>
            <p>Ausschließlich Mitglieder der Gruppe Datenschutzbeauftragte dürfen aktuellen Nextcloud-Administrationskonten fachlichen Vollzugriff erteilen. Maximal 24 Stunden sind zulässig.</p>
            <form id="brtop-full-access-form"><label>Admin-Benutzerkennung <input name="targetUid" required maxlength="64" autocomplete="off"></label><label>Dauer <select name="durationMinutes" required><option value="60">1 Stunde</option><option value="240">4 Stunden</option><option value="480">8 Stunden</option><option value="1440">24 Stunden</option></select></label><label><input id="brtop-full-access-enabled" name="enabled" type="checkbox" required> Vollzugriff für diesen Zeitraum aktivieren</label><button type="submit" class="primary">Freigabe aktivieren</button></form>
            <p id="brtop-full-access-status" role="status" aria-live="polite"></p>
            <div class="brtop-table-wrap" tabindex="0" role="region" aria-label="Protokollierte Admin-Vollzugriffszeiträume"><table><caption>Protokollierte Admin-Vollzugriffszeiträume</caption><thead><tr><th>Ziel-Admin</th><th>Freigegeben von</th><th>Von</th><th>Geplant bis</th><th>Tatsächlich bis / Status</th><th>Aktion</th></tr></thead><tbody id="brtop-full-access-history"><tr><td colspan="6">Freigaben werden geladen.</td></tr></tbody></table></div>
        </section>
    <?php endif; ?>

    <?php if ($_['hasBrTopAccess'] ?? false): ?>
    <section id="sessions-view" class="brtop-card brtop-view is-active" aria-hidden="false">
        <div class="brtop-section-head">
            <h2>Sitzungen</h2>
            <div class="brtop-actions">
                <button id="manage-legislature" type="button" hidden>Legislatur verwalten</button>
                <?= UiComponents::preset('newMeeting', ['id' => 'new-meeting']) ?>
            </div>
        </div>
        <div id="state"></div>
    </section>

    <section id="meeting-detail-view" class="brtop-card brtop-view" aria-hidden="true" hidden>
        <div class="brtop-section-head">
            <h2 id="meeting-detail-heading">Sitzung</h2>
            <div class="brtop-actions">
                <?= UiComponents::preset('backSessions', ['id' => 'back-to-sessions']) ?>
                <button id="review-absences" type="button" hidden>Verhinderungen prüfen</button>
                <?= UiComponents::preset('createInvitation', ['id' => 'detail-create-invitation']) ?>
                <?= UiComponents::preset('editProtocol', ['id' => 'detail-edit-protocol']) ?>
            </div>
        </div>

        <div id="meeting-detail-content"></div>

        <?php require __DIR__ . '/partials/top-form.php'; ?>

        <div id="meeting-documents"></div>
    </section>

    <section id="protocol-view" class="brtop-card brtop-view" aria-hidden="true" hidden>
        <div class="brtop-section-head">
            <h2 id="protocol-heading">Protokoll bearbeiten</h2>
            <div class="brtop-actions">
                <?= UiComponents::preset('backDetail', ['id' => 'back-to-detail']) ?>
                <?= UiComponents::preset('generateProtocolDocument', ['id' => 'generate-protocol-document']) ?>
            </div>
        </div>
        <div id="protocol-editor"></div>
    </section>

    <section id="legislature-view" class="brtop-card brtop-view" aria-hidden="true" hidden>
        <div class="brtop-section-head">
            <h2>Legislatur verwalten</h2>
            <button id="back-from-legislature" type="button">Zurück zu den Sitzungen</button>
        </div>
        <div id="legislature-editor"></div>
    </section>

    <section id="absence-view" class="brtop-card brtop-view" aria-hidden="true" hidden>
        <div class="brtop-section-head">
            <h2>Verhinderungen bestätigen</h2>
            <button id="back-from-absences" type="button">Zurück zur Sitzung</button>
        </div>
        <div id="absence-review"></div>
    </section>

    <?php endif; ?>

</div>

<?php if ($_['hasBrTopAccess'] ?? false): ?>
<div id="document-overlay" class="brtop-document-overlay" hidden>
    <div class="brtop-document-overlay-panel" role="dialog" aria-modal="true" aria-labelledby="document-overlay-title">
        <div class="brtop-document-overlay-head">
            <h2 id="document-overlay-title">Dokument</h2>
            <?= UiComponents::preset('close', ['id' => 'document-overlay-close']) ?>
        </div>
        <iframe id="document-overlay-frame" title="Dokumentvorschau"></iframe>
    </div>
</div>
<?php endif; ?>
