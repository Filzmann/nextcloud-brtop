<?php
use OCA\BrTop\View\UiComponents;

script('localbase', 'api/api-client');
script('brtop', 'modules/api');
script('localbase', 'ui/ui');
script('orgsuite', 'suite-navigation');
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
style('brtop', 'style');
style('orgsuite', 'suite-navigation');
?>

<div id="brtop-app">
    <div class="orgsuite-host" data-orgsuite data-suite="br" data-current-app="brtop"></div>
    <h1>BR TOP- und Sitzungsverwaltung</h1>
    <div id="brtop-notice" class="brtop-notice" role="status" aria-live="polite" hidden></div>

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

</div>

<div id="document-overlay" class="brtop-document-overlay" hidden>
    <div class="brtop-document-overlay-panel" role="dialog" aria-modal="true" aria-labelledby="document-overlay-title">
        <div class="brtop-document-overlay-head">
            <h2 id="document-overlay-title">Dokument</h2>
            <?= UiComponents::preset('close', ['id' => 'document-overlay-close']) ?>
        </div>
        <iframe id="document-overlay-frame" title="Dokumentvorschau"></iframe>
    </div>
</div>
