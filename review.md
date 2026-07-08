# Review: depa/sulu-block-helper & depa/sulu-block-content

> **Living checklist** — dieses Dokument ist die bundle-übergreifende Review-Liste für
> `sulu-block-helper` **und** `sulu-block-content` und wird beim Abarbeiten laufend
> aktualisiert (`[x]` + „Erledigt"-Notiz mit Fix-Commit je Punkt). Es liegt bewusst hier
> im Helper-Repo (Shared Foundation); der ursprüngliche „Stand" unten ist die Review-
> Baseline, der jeweils aktuelle Fix-Commit steht bei jedem erledigten Punkt.

Stand: 2026-07-02 · Reviewte Stände: helper `4d250f9`, content `54628a3` (jeweils `main`, Working Tree)
Verifikation: `bin/websiteconsole lint:twig` (25 ok / 3 Fehler), `xmllint` gegen Sulus `template-1.0.xsd` (alle XMLs valide), PHPUnit (4/4 grün), PHPStan L8 (0 Fehler), Abgleich gegen `sulu/sulu` 3.0.7.

Empfohlene Reihenfolge: **K1 → K5 → K2/K3 → W1–W3/W5/W8 → K4+W6/W10 → W4/W7/W9/W11/W12 → E-Punkte**

---

## 🔴 Kritisch (bricht Admin oder Website)

### K1 — content: Streutext `Da` bricht FAQ-Block
- [x] `Resources/views/includes/blocks/block--content-faq.html.twig:1`
- `Da{% extends 'includes/blocks/block--content-accordion.html.twig' %}` → Twig `SyntaxError: A template that extends another one cannot include content outside Twig blocks`. **Jede Seite/Preview mit FAQ-Block liefert 500.** Seit Commit `efe5178`.
- **Fix:** die zwei Zeichen `Da` entfernen.

### K2 — content: `block--content-action-button` hat kein Twig-Partial
- [ ] XML vollständig (`Resources/config/blocks/block--content-action-button.xml`), in `_slots.yaml:20` gelistet, erscheint im Admin — aber `Resources/views/includes/blocks/block--content-action-button.html.twig` fehlt → `LoaderError` beim ersten Einsatz.
- **Fix:** Partial ergänzen (analog `block--content-button`; Felder: `button_text`, `aria_label`, `attr_class`, `icon`) **oder** Block + Slots-Eintrag entfernen.

### K3 — content: `block--content-account-address.html.twig` dreifach defekt
- [x] Z. 4–19: nutzt Variable `account`, XML definiert `content.account` (`block--content-account-address.xml:10`, `single_account_selection`, mandatory) → immer „keine Organisation“-Fallback bzw. RuntimeError bei `strict_variables`.
- [x] Z. 11 `country(...)` und Z. 13 `obfuscate(...)`: Twig-Funktionen existieren nirgends (weder Bundle, Helper, preview-nav noch Projekt) → `Unknown "country" function` (lint-verifiziert) → Seiten-Crash.
- **Fix:** `content.account` verwenden; `country()` → `address.countryCode|country_name` (twig/intl-extra); `obfuscate` als Twig-Extension mitliefern oder Block entfernen/als projektspezifisch dokumentieren.
- **Erledigt** (`sulu-block-helper@812d1a2`, `sulu-block-content@f355001`): Entscheidung „beide in den Helper (self-contained)“. Helper bringt jetzt `CountryTwigExtension` (`country()` via `symfony/intl`, guarded) und `ObfuscateExtension` (`obfuscate` Filter+Funktion, ROT13) mit, registriert als autoconfigured Services (`Resources/config/services.yaml`, geladen in `SuluBlockHelperExtension::load()`); dazu das Website-JS `Resources/js/website/index.js` (ROT13-Decoder, selbst-initialisierend) + README-Doku + `symfony/intl`/`twig/twig` als require. Content-Twig liest jetzt `content.account`. Verifiziert: Website-Twig registriert `country`/`obfuscate`, `lint:twig` des Blocks grün. **Offen projektseitig:** Das obfuscate-Website-JS muss im konsumierenden Projekt in den Website-Build importiert werden (Test-Projekt hat keinen Website-JS-Build → dort nicht end-to-end baubar); außerdem setzt der Block voraus, dass `content.account` zur Account-Entity auflöst (Muster wie Snippet-Block, mit echten Daten noch zu prüfen).

### K4 — content: `block--content-form` — undeklarierte Abhängigkeit `sulu/form-bundle`
- [x] `block--content-form.html.twig:12` `sulu_form_build()` + Feldtyp `single_form_selection` (`block--content-form.xml:12`) stammen aus SuluFormBundle — weder in composer.json noch README, im Referenzprojekt nicht installiert → `Unknown "sulu_form_build" function` (lint-verifiziert); Admin-Feldtyp unbekannt.
- [x] Z. 15/17 referenzieren Projekt-Templates `form/floating-theme.html.twig` / `form/theme.html.twig`, die das Bundle nicht mitliefert.
- **Fix:** `sulu/form-bundle` als require oder mindestens `suggest` + README-Abschnitt; Form-Themes mitliefern oder Fallback auf Standard-Theme.
- **Erledigt** (`sulu-block-content@41b4c1f`): `sulu/form-bundle` als `suggest` deklariert (nur 1 von 30 Blocks braucht es); Standard-Theme auf das echte, mitgelieferte `@SuluForm/themes/basic.html.twig` umgestellt statt der nirgends existierenden `form/theme.html.twig`; „Floating labels“ bleibt dokumentierter Projekt-Override (kein Bundle liefert diese Variante); README-Abschnitt „Optional: `block--content-form`“ ergänzt. `sulu_form_build()` bleibt im Test-Projekt erwartungsgemäß unbekannt, bis dort `composer require sulu/form-bundle` läuft.

### K5 — helper: `config_line`-Registrierung crasht Admin bei Doppelregistrierung
- [x] `Resources/js/index.js:11` — `fieldRegistry.add('config_line', Input)` ohne Guard. `FieldRegistry.add()` wirft bei bereits registriertem Key hart (`fieldRegistry.js:19-21`). Ist `robole/sulu-ai-translator-bundle` (registriert `config_line` ebenfalls) parallel im Admin-Build, **crasht das gesamte Admin-JS beim Boot**. Der Code-Kommentar (Z. 9–10, „no conflict“) ist falsch.
- **Fix:**
  ```js
  if (!fieldRegistry.has('config_line')) {
      fieldRegistry.add('config_line', Input);
  }
  ```
  und Kommentar korrigieren.

---

## 🟠 Wichtig

### W1 — content: `enable_x2` doppelt definiert
- [x] `block--content-image.xml` — lokal in Sektion `config_1` (Z. 95–104) **und** via XInclude `config_image.xml` (Z. 107) → zwei Toggler im Admin auf demselben Datenkey.
- **Fix:** lokale Definition (Z. 95–104) löschen.
- **Erledigt** (`sulu-block-content@4ef8f5b`): lokale Definition entfernt, Fragment bleibt einzige Quelle. Verifiziert: `xmllint --xinclude --schema` validiert, `bin/adminconsole cache:clear` fehlerfrei.

### W2 — content: doppelter Type-Ref in box
- [x] `block--content-box.xml` — `<type ref="block--content-col-lead"/>` in Z. 36 **und** Z. 49.
- **Fix:** Z. 49 entfernen.
- **Erledigt** (`sulu-block-content@4ef8f5b`): zweites Vorkommen entfernt, alle `<type ref>` in box.xml jetzt eindeutig (verifiziert per grep+uniq). `xmllint --xinclude --schema` validiert.

### W3 — content: `button-grid`-Twig nutzt nicht existierendes Feld
- [x] `block--content-button-grid.html.twig:3` — `content.col_attr_class` existiert in der XML nicht (nur `blocks` + `attr_class`) → Spalten-Klasse (Z. 7) immer leer, für Redakteure nicht setzbar.
- **Fix:** Property `col_attr_class` in XML ergänzen **oder** aus dem Twig entfernen.
- **Erledigt** (`sulu-block-content@e3e0ac4`): Property `col_attr_class` ergänzt — **inline** in `block--content-button-grid.xml`, nicht als `_fragments/`-Include. Vorab geprüft: kein anderer Block in allen 8 Bundles hat ein vergleichbares Pro-Spalte/Pro-Item-Klassenfeld (kein Wiederverwendungsfall) → Fragment/Helper-Include wäre premature Abstraction gewesen. Verifiziert: `xmllint --xinclude --schema` validiert, Feld im Block-Metadaten-Cache, `lint:twig` grün.

### W4 — content: Übersetzungen werden nie geladen
- [x] Bundle-Klasse liegt in `src/` → `Bundle::getPath()` = `src/`; Symfony sucht nur `<bundlePath>/translations` bzw. `<bundlePath>/Resources/translations` (FrameworkExtension:1691). `Resources/translations/messages.{de,en}.yaml` auf Repo-Root sind unsichtbar → `'block.account_address.no_address'|trans` rendert den rohen Key.
- **Fix:** Translator-Pfad im Extension-`prepend()` registrieren (analog Twig-Pfade) oder Dateien nach `src/Resources/translations/` verschieben.
- **Erledigt** (`sulu-block-content@dead0e4`): Analyse vorab dreifach re-verifiziert (bundles_metadata-Pfad = `src/`; FrameworkExtension:1691 scannt nur die zwei Bundle-relativen Orte, Container listete `src/translations` als scanned-but-missing; `debug:translation de` meldete beide Keys `missing`, App definiert sie nicht). Entscheidung: Dateien per `git mv` nach `src/translations/` verschoben (statt prepend-Variante im Helper). Verifiziert: `debug:translation de/en` löst beide Keys jetzt auf („Keine Adresse hinterlegt" / „No address stored"), kein `missing` mehr.
- **Nachtrag / Root Cause (`content@cd5eb2d`, `helper@e4181a9`):** Die eigentliche Ursache war die nicht-standardkonforme Bundle-Struktur (PHP in `src/`, aber `Resources/` im Root ohne `getPath()`-Override). Beide Bundles wurden auf **Symfony `AbstractBundle` + flache Struktur** migriert (`config/`, `templates/`, `translations/`, `assets/`; DI-Logik in neuer Basisklasse `AbstractBlockBundle`). Die Übersetzungen liegen dadurch jetzt in **`translations/`** (Repo-Root, von `AbstractBundle::getPath()` = Root gescannt), nicht mehr in `src/translations/`. Miterledigt: der **is_dir-Guard**-Teil von W11 (prependExtension guardet Twig-/Block-Verzeichnisse jetzt) und Teile von W7 (Struktur-Konsistenz). **Noch offen aus W11:** die Twig-Pfade werden weiterhin im Default-Namespace (nicht `@SuluBlockHelper`) registriert — Shadowing-Thema bleibt.

### W5 — content: ungültiges JSON-LD im FAQ-Block
- [x] `block--content-faq.html.twig:25` — `{% if not loop.last %}{% endif %}` mit leerem Body → fehlendes Komma zwischen `Question`-Objekten → ab 2 Einträgen invalides `FAQPage`-Schema (Google verwirft es). Wird erst nach K1 sichtbar.
- **Fix:** `,` in den if-Body.
- **Erledigt** (`sulu-block-content@b6512eb`): Komma ergänzt. Funktional bewiesen per isoliertem Twig-Render (3 Fake-FAQ-Einträge, `script`-Block gerendert, JSON extrahiert): **vorher** `json_decode` → Syntax-Fehler (Bug bestätigt), **nachher** valide mit allen 3 `Question`-Objekten.

### W6 — content: README stimmt nicht mit Bestand überein
- [ ] `README.md:3` „29 blocks“ / Tabelle: tatsächlich 30 Definitionen; **`template-var` fehlt** (dient als Kind von `block--content-html-template` — dokumentieren).
- [ ] `README.md:46-50`: `composer require` allein funktioniert nicht (proprietär, nicht auf Packagist). `repositories` in der Bundle-composer.json wirkt nur im Root-Kontext; Konsumenten müssen VCS-Repos für content **und** helper (ggf. preview-nav) selbst eintragen → ins README.
- [ ] `README.md:22` „Raw HTML block“ — `block--content-html` nutzt `text_editor` (CKEditor), kein Raw-Feld.

### W7 — beide: „Shared“ Fragmente sind dupliziert statt geteilt
- [x] `attr_class.xml`, `attr_id.xml`, `config_image.xml` liegen **byte-identisch** in helper und content; referenziert werden per `<xi:include href="../_fragments/…">` ausschließlich die **lokalen** Kopien → Helper-Fragmente sind toter Code, Drift vorprogrammiert.
- [x] `block--content-title-icon.xml:87-148` dupliziert die `config_image`-Felder ein **drittes** Mal inline — bereits gedriftet (ohne `enable_x2`/colspan). → **Geprüft, absichtlich inline gelassen** (Nutzer-Entscheidung 2026-07-08): kein Drift, sondern bewusster 3-Felder-Subset für einen SVG-Block (`enable_x2` bei SVG sinnlos, Twig nutzt es nicht); Fragment-Einbindung würde ein totes Feld ergänzen, eigenes Fragment wäre Single-Consumer. Keine Codeänderung.
- **Fix:** Konsumenten auf die Helper-Kopie umstellen (Pakete liegen in `vendor/depa/` nebeneinander: `href="../../../sulu-block-helper/Resources/config/_fragments/attr_class.xml"`) **oder** CI-Sync-Check, der die Kopien diff’t.
- **Erledigt — Variante A (CI-Sync-Check), Umfang auf 8 Bundles erweitert** (content `1c6fa04`, swiper `2fbfab7`, layout `267f66b`, article `bb172ac`, hero `23d1088`, section `d5390aa`, grid `cc6b7a7`): Cross-Package-XInclude (Variante B) wurde verworfen — fragil wegen der `vendor/`-Symlinks im Dev-Setup und bricht bei Standalone-Nutzung. Stattdessen: alle 7 Consumer-Bundles behalten ihre lokale `config/_fragments/`-Kopie (self-contained), aber ein neuer CI-Job `sync-fragments` checkt `depa-berlin/sulu-block-helper@main` aus und diff't die lokalen Fragmente gegen die kanonische Helper-Version — schlägt bei Drift oder fehlendem Gegenstück fehl. Beide Repos sind PUBLIC → Cross-Repo-Checkout ohne Token. Verifiziert: Diff-Logik lokal (PASS synchron / FAIL bei künstlichem Drift), YAML aller 7 valide, **GitHub-Actions-Lauf grün** (Job „_fragments in sync with helper" ✓, 31s). Der Helper selbst braucht den Job nicht (er IST die Quelle).
- **⚠️ title-icon-Inline-„Duplikat" — Befund weicht vom Review ab:** siehe separate Analyse unten / offene Rückfrage. Das Twig nutzt `loading`/`fetchpriority_high`/`is_decorative`, **nicht** `enable_x2` — korrekt, da title-icon ein **SVG-Icon** rendert (Retina/2x sinnlos). Das Inline ist also ein **bewusster 3-Felder-Subset**, kein versehentlicher Drift; der Review-Vorschlag „durch `config_image.xml`-Include ersetzen" würde fälschlich `enable_x2` hinzufügen (totes + semantisch falsches Feld). Ein eigenes 3-Felder-Fragment wäre Single-Consumer → widerspricht dem gerade bei W3 etablierten Prinzip. **Empfehlung: inline lassen.** (Checkbox bewusst offen bis Nutzer-Entscheidung.)

### W8 — content: Preview-Attribut fehlt am title-icon-Hauptelement
- [ ] `block--content-title-icon.html.twig:28-32` — nur der Fehler-Alert (Z. 16) hat `sulu_block_preview(content)` → Preview-Klick-Navigation für den Block tot.
- **Fix:** Aufruf nach dem aria-Include am Hauptelement ergänzen.

### W9 — content: `_slots.yaml` ohne Konsument + Asymmetrien
- [ ] Alle gelisteten Keys existieren ✓, aber: das in Commit `cdf624d` erwähnte `GenerateSlotsCommand`/`BlockSlotCollector` existiert nirgends → tote Konfiguration.
- [ ] Inhaltlich: `block--content-faq` und `block--content-title-icon` fehlen komplett; `block--content-html-template` fehlt bei `container`; `asset-container`/`account-address` fehlen ganz; `action-button` gelistet, obwohl nicht renderbar (K2).
- **Fix:** Konsument bauen/nachziehen oder Datei entfernen; Listen vervollständigen.

### W10 — helper: composer.json — genutzte Abhängigkeiten nicht deklariert
- [x] `ext-simplexml` / `ext-libxml`: `BlockMetadataLoaderTrait` nutzt `simplexml_load_file()` + `libxml_*` direkt.
- [x] `symfony/finder`: direkt genutzt (`BlockMetadataLoaderTrait.php:7`), nur transitiv vorhanden.
- [x] `sulu/sulu` fehlt komplett (kommt nur transitiv über preview-nav), obwohl `sulu_admin`-Config geprependet und Sulu-Template-XMLs ausgeliefert werden.
- **Fix:** `"ext-simplexml": "*", "ext-libxml": "*", "symfony/finder": "^7.0", "sulu/sulu": "~3.0"` ergänzen. (Content: ebenfalls `sulu/sulu ^3.0` + `suggest: sulu/form-bundle`, siehe E5.)
- **Erledigt** (`sulu-block-helper@7e92d91`, `sulu-block-content@fd5c2cc`): Helper deklariert jetzt `ext-simplexml`, `ext-libxml`, `symfony/finder ^7.0`, `sulu/sulu ~3.0`; Content deklariert `sulu/sulu ~3.0` (das `suggest: sulu/form-bundle` aus E5/K4 war schon vorhanden). Verifiziert im Test-Projekt: `composer update` löst konfliktfrei auf, `composer check-platform-reqs` meldet beide Extensions `success`, `composer show` bestätigt alle neuen Zeilen, Admin- und Website-Cache bauen fehlerfrei.

### W11 — helper: `prepend()` ohne Existenz-Checks + Namespace-Problem
- [ ] `AbstractBlockExtension.php:30-51` — `getViewsDir()`/`getBlocksDir()` ungeprüft registriert; Twig-Bundle fügt Pfade ohne `file_exists`-Check hinzu (TwigExtension.php:125-131) → Schwester-Bundle ohne `Resources/views` produziert `Twig\Error\LoaderError`. Fix: beide Blöcke mit `is_dir()` guarden.
- [ ] Z. 32–38 — Views landen im **Default-Namespace** (`paths: {dir: null}`), vor `twig.default_path` → Bundle-Templates shadowen App-Templates; Projekt kann Partials nicht überschreiben; Schwester-Bundles mit gleichem relativen Pfad kollidieren stumm. Symfony registriert ohnehin `@SuluBlockHelper` automatisch (inkl. Override via `templates/bundles/SuluBlockHelperBundle/`).
- **Fix:** Includes auf `@SuluBlockHelper/…` umstellen und Twig-Prepend entfernen (erfordert Anpassung der Include-Pfade in Konsumenten, z. B. `block--content-title.html.twig:6`); mindestens dokumentieren.

### W12 — helper: README unvollständig / nicht ausführbar
- [ ] `README.md:49-51` — `composer require` schlägt ohne `repositories`-Eintrag fehl (proprietär). Fehlt außerdem:
  - die mitgelieferten globalen Block-Templates `aria-attr--aria-label.xml` / `aria-attr--heading.xml` (Kernstück, werden per `<type ref>` referenziert),
  - die von `AbstractBlockExtension` erzwungene Verzeichnis-Konvention (`src/DependencyInjection/` + `Resources/config/blocks` + `Resources/views` wegen `getReflectionDir() . '/../../…'`),
  - Hinweis, dass `SuluPreviewNavBundle` registriert sein muss (liefert `sulu_block_preview`).

### W13 — helper: Robustheit `BlockMetadataLoaderTrait`
- [ ] Z. 27: `$file->getRealPath()` kann `false` liefern → `TypeError`; `getPathname()` verwenden.
- [ ] Z. 43: XPath `//s:key` matcht überall; präziser `/s:template/s:key`.
- [ ] Doppelte `<key>`s über Dateien werden nicht erkannt (`$blocks`-Duplikate, `$children` stumm überschrieben). Fix: Duplikat-Check + Exception (fail-fast).

---

## 🟡 Empfehlungen

### E1 — CI-Lücken schließen (hätte alle K-Funde gefangen)
- [ ] content: `twig lint`-Step (fängt K1/K3/K4), Test „jeder XML-Key hat ein Twig-Partial“ (fängt K2), `xmllint --xinclude --schema` über `Resources/config/blocks/`, Duplikat-Check für Property-Namen/Type-Refs (W1/W2).
- [ ] helper: PHP-Matrix um 8.4 ergänzen; `composer validate --strict`; PHPStan-Step mit `--memory-limit=512M` (Default 128M crasht lokal).
- [ ] helper `phpstan.neon`: Level `max` statt 8 erwägen; `treatPhpDocTypesAsCertain: false` verdeckt W13; `phpstan/phpstan-symfony` ist deklariert aber nie aktiviert — aktivieren oder entfernen.

### E2 — `|raw`/XSS-Trust-Modell dokumentieren (content)
- [ ] Undokumentiert raw: `block--content-html.html.twig:5`, `inline-svg:7` (beliebiges SVG inkl. `<script>`), `lead-html:5`, `col-lead-html:6`, `accordion-item:25` (body), `form:32` (successText). Risiko auf CMS-Redakteure beschränkt (übliches Sulu-Modell) — als Security-Hinweis ins README. `html-template` hat bereits einen sauberen Inline-Kommentar ✓; `attr_class`/`attr_id` durch Autoescaping abgesichert ✓.

### E3 — `|raw`-Konstrukt im helper-Partial entschärfen
- [ ] `aria_attributes.html.twig:1-8` — aktuell kein XSS, aber fragil: `include()|trim` verliert das Safe-Flag, `{{ aria|raw }}` gibt ungeprüft aus. Ohne `raw` lösbar:
  ```twig
  {%- for item in aria_blocks|default([]) %} {{ include('…/aria/' ~ item.type ~ '.html.twig', {item: item}) }}{% endfor -%}
  ```
- [ ] `item.aria_label|default` bzw. `item.aria_level|default(2)` in den aria-Partials (Altbestand/`strict_variables`); ggf. `ignore_missing` beim dynamischen Include.

### E4 — Inkonsistentes `|default` (content, bricht bei `strict_variables`)
- [ ] U. a.: `asset-container:7` (`asset_typ`, `animation`, `attr_class`), `video:4-7,19`, `image:3,5,12` (`loading`, `fetchpriority_high`, `element`), `headline:4` (`icon_type`), `list:3` (`list_typ`), `button-multiline:3-5`, `list-item:6` (`view.link.target`), `faq:4` (`attr_class`). Einheitlich `|default` verwenden.

### E5 — composer.json-Hygiene
- [ ] content: `"depa/sulu-block-helper": "@dev"` durch versionierte Angabe ersetzen; `sulu/sulu ^3.0` ergänzen; `suggest: sulu/form-bundle` (K4). `minimum-stability`/`repositories` wirken nur als Root (CI) — für Konsumenten ins README (W6).
- [ ] helper: `minimum-stability: dev` ist Root-only, wirkungslos in einer Bibliothek. `lubomirfiala/sulu-preview-nav` als Helper-Requirement ist vertretbare „Platform“-Entscheidung — sauberer wäre Deklaration im content-Bundle, dessen Templates die Funktion tatsächlich aufrufen.

### E6 — Projekt-Abhängigkeiten der Partials dokumentieren (content)
- [ ] 22 vorausgesetzte Bildformate (`content-image-*` in `image:15-49`, `asset-*` in `asset-container:15-61`) — Testprojekt definiert nur `300x` → leere `src`. Formatliste/`image-formats.xml`-Snippet ins README.
- [ ] `video:9-10`: Twig-Global `asset_collector` (nirgends definiert) + Assets `/website/styles|js/block--content-video.*`, die das Bundle nicht mitbringt — in prod still geschluckt, in dev/strict Fehler.

### E7 — Admin-Texte/Übersetzungen (content)
- [ ] Properties ganz ohne `<meta><title>`: `text` in `html:18`, `text`, `lead`, `lead-html`, `col-lead`, `col-lead-html`; `image` in `image.xml:12`; `html` in `html-template.xml:18`; `image1-3` in `asset-container.xml:54-70`; `dataAttributes` in `form.xml:83`.
- [ ] Nur-en: „Sub Blocks“ (`box.xml:30`, `form.xml:30`, `button-content.xml:36`). Deutsch unter `lang="en"`: `box.xml:17` („Bemerkung“), `asset-container.xml:17` („Darstellung“). Tippfehler „Nummierierte“ (`accordion.xml:57`, `faq.xml:56`).
- [ ] `template-var.xml`: `pattern_message` nur englisch (Z. 22), leere `info_text` (Z. 16–17).

### E8 — Kleinigkeiten
- [ ] content: `box.xml:11` Template-Level-Tag `sulu.block_preview` vermutlich wirkungslos (nur auf Property-Ebene sinnvoll); `button-content.xml:34` `blocks` ohne `default-type` (explizit setzen); `comment`-Property in `box.xml:14` wird im Twig nie genutzt (als Redakteurs-Notiz kommentieren).
- [ ] helper: libxml-Zustand restaurieren statt hart zurücksetzen (`$prev = libxml_use_internal_errors(true); … libxml_use_internal_errors($prev);`, Trait Z. 26–30); `$configs` in `load()` stumm ignoriert → leere `Configuration` + `processConfiguration()` oder dokumentieren; Parameter `*.bundle_metadata`/`*.blocks_dir` werden nirgends gelesen — Zweck dokumentieren oder entfernen.
- [ ] helper Tests: `BlockMetadataLoaderTrait` ungetestet (kaputtes XML → RuntimeException, fehlendes Verzeichnis, Key-/children-Extraktion inkl. ref-Dedupe); `sulu_admin`-Prepend-Zweig ungetestet; `testLoadDoesNotThrow` sollte Parameter-Inhalte asserten.
- [ ] helper `.gitignore`: `composer.lock` fehlt; `/var/`, `/.env.local` sind Skeleton-Reste.
- [ ] helper XML-Kosmetik: doppelte Punkte „..“ in `aria-attr--aria-label.xml:16-17`; `config_image.xml:66` „kennzeichen“ → „kennzeichnen“; Z. 37 „auf dem letzten Drücker“ → z. B. „verzögert (lazy)“; `@see`-Links auf Sulu-2.2-Doku passen nicht zu den Feldtypen; `aria-attr--heading.xml` nur Level 1–4 (falls Absicht, ok); Leerzeichen vor `>` in `_fragments/attr_class.xml:6` / `attr_id.xml:6`.

---

## ✅ Positiv verifiziert

- Alle XMLs (beide Bundles) validieren gegen Sulus `template-1.0.xsd`; alle `<type ref>` zeigen auf existierende Keys (inkl. `aria-attr--*` aus dem Helper).
- `sulu_admin.templates.block.directories`-Prepend ist valides Sulu-3.0-Muster; Twig-Partial-Namen matchen die Block-Keys; JS-Feldtyp-Registrierung entspricht dem Sulu-Kern-Muster.
- helper: PHPUnit 4/4 grün, PHPStan L8 0 Fehler; Extension-Konventionen (Alias, Auto-Discovery, Reflection-Pfade) korrekt, am Konsumenten verifiziert.
- content: 25 von 28 Twig-Partials lint-sauber; Tests/CI vorhanden (DI-Tests, PHP 8.2/8.3, PHPStan L8).
