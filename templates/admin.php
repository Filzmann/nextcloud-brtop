<?php
script('localbase', 'api/api-client');
script('brtop', 'modules/api');
script('brtop', 'admin-document-configuration');
style('brtop', 'style');
?>
<section id="brtop-admin" class="section brtop-document-configuration" aria-labelledby="brtop-admin-heading">
    <h2 id="brtop-admin-heading">Dokumentkonfiguration</h2>
    <p>Organisationstexte und Vorlagen werden getrennt verwaltet. Erst freigegebene Vorlagen werden für neue Dokumente verwendet; bereits erzeugte Dokumente bleiben unverändert.</p>
    <p id="brtop-document-configuration-status" role="status" aria-live="polite"></p>
    <form id="brtop-document-organization-form" class="brtop-document-configuration__card">
        <h3>Organisationsstammdaten</h3>
        <div class="brtop-form-grid"><label for="brtop-document-council-name">Gremienname <input id="brtop-document-council-name" name="councilName" required maxlength="255"></label><label for="brtop-document-issue-place">Ausstellungsort <input id="brtop-document-issue-place" name="issuePlace" required maxlength="255"></label></div>
        <label for="brtop-document-council-address">Anschrift <textarea id="brtop-document-council-address" name="councilAddress" required maxlength="2000" rows="3"></textarea></label>
        <label for="brtop-document-committee-codes">Ausschusskatalog (JSON) <textarea id="brtop-document-committee-codes" name="committeeCodesJson" required rows="4" aria-describedby="brtop-document-committee-codes-help"></textarea></label>
        <small id="brtop-document-committee-codes-help">Zum Beispiel: [{"value":"ASA","label":"ASA"}]. Codes sind Großbuchstaben, Zahlen, Bindestriche oder Unterstriche.</small>
        <label for="brtop-document-invitation-salutation">Anrede <textarea id="brtop-document-invitation-salutation" name="invitationSalutation" required maxlength="500" rows="2"></textarea></label>
        <label for="brtop-document-invitation-introduction">Einleitung der Einladung <textarea id="brtop-document-invitation-introduction" name="invitationIntroduction" required maxlength="4000" rows="4"></textarea></label>
        <div class="brtop-form-grid"><label for="brtop-document-invitation-closing">Grußformel <textarea id="brtop-document-invitation-closing" name="invitationClosing" required maxlength="500" rows="2"></textarea></label><label for="brtop-document-invitation-sender">Absender <textarea id="brtop-document-invitation-sender" name="invitationSender" required maxlength="500" rows="2"></textarea></label></div>
        <button class="primary" type="submit">Stammdaten speichern</button>
    </form>
    <section class="brtop-document-configuration__card" aria-labelledby="brtop-document-template-heading">
        <h3 id="brtop-document-template-heading">Versionierte Dokumentvorlagen</h3>
        <p>Vor dem Speichern wird die Vorlage geprüft. Entwürfe werden erst nach ausdrücklicher Freigabe aktiv.</p>
        <div id="brtop-document-template-list" class="brtop-document-template-list" role="list" aria-label="Vorlagen und Versionen"></div>
        <div id="brtop-document-template-editor" hidden aria-live="polite">
            <h4 id="brtop-document-template-editor-heading" tabindex="-1">Vorlage bearbeiten</h4><p id="brtop-document-template-meta"></p>
            <label id="brtop-document-template-content-label" for="brtop-document-template-content">Vorlageninhalt <textarea id="brtop-document-template-content" rows="16" spellcheck="false"></textarea></label>
            <label id="brtop-document-template-file-label" for="brtop-document-template-file" hidden>ODT-Datei <input id="brtop-document-template-file" type="file" accept="application/vnd.oasis.opendocument.text,.odt"></label>
            <div class="brtop-actions"><button id="brtop-document-template-preview-button" type="button">Vorschau prüfen</button><button id="brtop-document-template-save-draft" type="button">Als Entwurf speichern</button><button id="brtop-document-template-approve" type="button" disabled>Ausgewählte Version freigeben</button></div>
            <div id="brtop-document-template-preview" class="brtop-document-template-preview" aria-live="polite"></div>
        </div>
    </section>
</section>
