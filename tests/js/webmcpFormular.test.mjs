// Tests für src/Resources/Vue/kontaktformular.vue — WebMCP-Anmerkungen (Task #1425, AK1–AK4).
// Lauf: node --test tests/js/webmcpFormular.test.mjs
// Voraussetzung: vue (Vollbuild) und vue-template-compiler 2.7 in einem node_modules oberhalb des Pakets
// (Host packages_dev); ohne beide wird übersprungen.
//
// Aufbau: Der Test liest die .vue-Datei per parseComponent, lädt den Skript-Teil als Modul und rendert die
// Komponente mit dem Vue-Vollbuild zu VNodes (ohne DOM, ohne Browser). Geprüft werden die Attribute der VNodes.
//
// Schnittstelle (vom Test festgelegt, der Coder baut danach):
//   <form>          Attribute `toolname` und `tooldescription` (aus den Props `toolName`, `toolDescription`,
//                   Strings; leer/fehlend → deutscher Standard, Name `kontaktformular_senden`), nie `toolautosubmit`.
//   Felder          `Name`, `Email`, `Telefon`, `Website` (nur mit Prop feldWebsite), `Nachricht`,
//                   `datenverarbeitung`, `fax`: je Attribut `toolparamdescription`, ohne `*`.
//   Checkbox        `datenverarbeitung` ohne `required`, mit `ref="einwilligung"` und Handler
//                   `v-on:change` oder `v-on:click` auf die Methode `einwilligungGeaendert`.
//   Methode absenden()  (Skript-Teil, this.$refs.einwilligung = Checkbox, this.$refs.formular, this.$refs.token):
//                   Ohne Haken: `einwilligung.setCustomValidity('Bitte stimmen Sie der Verarbeitung Ihrer Daten zu.')`,
//                   `einwilligung.reportValidity()`, kein Abruf des Tokens, kein `formular.submit()`.
//                   Mit Haken: `setCustomValidity('')` und der bisherige Ablauf (Token holen, submit()).
//   Methode einwilligungGeaendert(): `einwilligung.setCustomValidity('')`.

import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, resolve } from 'node:path';
import { test } from 'node:test';
import { fileURLToPath, pathToFileURL } from 'node:url';

const hier = dirname(fileURLToPath(import.meta.url));
const vueDatei = resolve(hier, '../../src/Resources/Vue/kontaktformular.vue');
const require = createRequire(import.meta.url);

let Vue;
let parseComponent;
let compileToFunctions;
try {
  const suche = { paths: [hier, '/home/joerg/projekte/packages_dev'] };
  Vue = require(require.resolve('vue/dist/vue.common.js', suche));
  const kompilierer = require(require.resolve('vue-template-compiler', suche));
  parseComponent = kompilierer.parseComponent;
  compileToFunctions = kompilierer.compileToFunctions;
} catch (fehler) {
  Vue = null;
}

const MELDUNG = 'Bitte stimmen Sie der Verarbeitung Ihrer Daten zu.';

async function ladeKomponente() {
  const teile = parseComponent(readFileSync(vueDatei, 'utf8'));
  const verzeichnis = pathToFileURL(dirname(vueDatei) + '/').href;
  const skript = teile.script.content
    .replace(/from\s+'\.\//g, `from '${verzeichnis}`)
    .replace('export default', 'export const komponente =');
  const modul = await import('data:text/javascript;base64,' + Buffer.from(skript).toString('base64'));
  return { optionen: modul.komponente, template: teile.template.content };
}

globalThis.window ??= { location: { href: 'https://example.test/seite' }, addEventListener() {}, removeEventListener() {} };

function elemente(vnode, liste = []) {
  if (vnode && vnode.tag) {
    liste.push(vnode);
    (vnode.children || []).forEach((kind) => elemente(kind, liste));
  }
  return liste;
}

async function rendere(propsData = {}) {
  const { optionen, template } = await ladeKomponente();
  Vue.config.productionTip = false;
  Vue.config.devtools = false;
  const vm = new Vue({ ...optionen, ...compileToFunctions(template), propsData: { route: '/anfrage', ...propsData } });
  const alle = elemente(vm._render());
  const attrs = (el) => el.data?.attrs ?? {};
  const form = alle.find((el) => el.tag === 'form');
  const feld = (name) => alle.find((el) => ['input', 'textarea'].includes(el.tag) && attrs(el).name === name);
  return { vm, optionen, form, feld, attrs };
}

const vorhanden = (wert) => wert !== undefined && wert !== null && wert !== false;
const opts = { skip: Vue ? false : 'vue oder vue-template-compiler nicht gefunden' };

// AK1
test('AK1: form trägt gefülltes toolname und tooldescription, aber kein toolautosubmit', opts, async () => {
  const { form, attrs } = await rendere();

  assert.ok(form, 'form gerendert (Gegenprobe: Aufbau greift)');
  assert.ok(String(attrs(form).toolname ?? '').trim().length > 0, 'toolname leer oder fehlt');
  assert.ok(String(attrs(form).tooldescription ?? '').trim().length > 0, 'tooldescription leer oder fehlt');
  assert.equal(vorhanden(attrs(form).toolautosubmit), false);
});

// AK2
test('AK2: Props bestimmen toolname und tooldescription', opts, async () => {
  const { form, attrs } = await rendere({ toolName: 'mein_tool', toolDescription: 'Meine Beschreibung' });

  assert.equal(attrs(form).toolname, 'mein_tool');
  assert.equal(attrs(form).tooldescription, 'Meine Beschreibung');
});

// AK2
test('AK2: ohne Prop oder mit leerem Wert gelten die deutschen Standardwerte', opts, async () => {
  const ohne = await rendere();
  const leer = await rendere({ toolName: '', toolDescription: '' });
  const gesetzt = await rendere({ toolName: 'x', toolDescription: 'y' });

  assert.notEqual(ohne.attrs(gesetzt.form).toolname, ohne.attrs(ohne.form).toolname, 'Gegenprobe: Prop wirkt');
  for (const r of [ohne, leer]) {
    assert.equal(r.attrs(r.form).toolname, 'kontaktformular_senden');
    const text = r.attrs(r.form).tooldescription;
    for (const teil of ['Name', 'Email', 'Nachricht', 'fax', 'selbst']) {
      assert.ok(text.includes(teil), `Standardbeschreibung nennt "${teil}" nicht`);
    }
    assert.match(text, /Datenverarbeitung|Zustimmung|Einwilligung/i);
  }
});

// AK3
test('AK3: jedes sichtbare Feld hat eine toolparamdescription ohne Stern, fax nennt Spamschutz und leer', opts, async () => {
  const { feld, attrs } = await rendere({ feldWebsite: true });

  for (const name of ['Name', 'Email', 'Telefon', 'Website', 'Nachricht', 'datenverarbeitung', 'fax']) {
    const el = feld(name);
    assert.ok(el, `Feld ${name} fehlt`);
    const text = String(attrs(el).toolparamdescription ?? '');
    assert.ok(text.trim().length > 0, `toolparamdescription fehlt bei ${name}`);
    assert.ok(!text.includes('*'), `toolparamdescription bei ${name} enthält *`);
  }
  assert.match(attrs(feld('fax')).toolparamdescription, /Spam/i);
  assert.match(attrs(feld('fax')).toolparamdescription, /leer/i);
});

// AK3
test('AK3: Website-Feld nur mit feld-website', opts, async () => {
  const mit = await rendere({ feldWebsite: true });
  const ohne = await rendere({ feldWebsite: false });

  assert.ok(mit.feld('Website'), 'Gegenprobe: mit feld-website vorhanden');
  assert.equal(ohne.feld('Website'), undefined);
  assert.ok(ohne.feld('Telefon'), 'Aufbau ohne Website greift');
});

// AK4
test('AK4: Checkbox ohne required, Beschreibung nennt Person selbst und leer lassen', opts, async () => {
  const { feld, attrs } = await rendere();
  const box = feld('datenverarbeitung');

  assert.ok(box);
  assert.equal(attrs(box).type, 'checkbox');
  assert.equal(vorhanden(attrs(box).required), false);
  assert.match(attrs(box).toolparamdescription, /selbst/i);
  assert.match(attrs(box).toolparamdescription, /leer/i);
  // Gegenprobe: required wird erkannt, Pflichtfelder bleiben
  assert.equal(vorhanden(attrs(feld('Name')).required), true);
  assert.equal(vorhanden(attrs(feld('Email')).required), true);
});

function attrappen({ haken }) {
  const aufrufe = [];
  const einwilligung = {
    checked: haken,
    setCustomValidity: (m) => aufrufe.push(['setCustomValidity', m]),
    reportValidity: () => aufrufe.push(['reportValidity']),
  };
  const formular = { submits: 0, submit() { this.submits += 1; } };
  const token = { value: 'alt' };
  return { aufrufe, einwilligung, formular, token };
}

// AK4
test('AK4: Absenden ohne Haken zeigt Meldung und sendet nicht, mit Haken sendet es', opts, async () => {
  const { optionen } = await ladeKomponente();
  const original = globalThis.fetch;
  let abrufe = 0;
  globalThis.fetch = async () => {
    abrufe += 1;
    return { status: 200, ok: true, json: async () => ({ token: 'neu' }) };
  };
  try {
    // mit Haken (Gegenprobe: der Aufbau sendet überhaupt)
    const a = attrappen({ haken: true });
    await optionen.methods.absenden.call({
      $refs: { formular: a.formular, token: a.token, einwilligung: a.einwilligung },
      tokenRoute: '/kontaktformular/csrf-token',
    });
    assert.equal(a.formular.submits, 1);
    assert.equal(abrufe, 1);
    assert.ok(a.aufrufe.some(([n, m]) => n === 'setCustomValidity' && m === ''));

    // ohne Haken
    const b = attrappen({ haken: false });
    await optionen.methods.absenden.call({
      $refs: { formular: b.formular, token: b.token, einwilligung: b.einwilligung },
      tokenRoute: '/kontaktformular/csrf-token',
    });
    assert.deepEqual(b.aufrufe, [['setCustomValidity', MELDUNG], ['reportValidity']]);
    assert.equal(b.formular.submits, 0);
    assert.equal(abrufe, 1, 'kein weiterer Token-Abruf');
  } finally {
    globalThis.fetch = original;
  }
});

// AK4
test('AK4: Klick auf die Checkbox nimmt die Meldung zurück', opts, async () => {
  const { optionen, vm, feld } = await rendere();
  const box = feld('datenverarbeitung');
  const handler = box.data?.on?.change ?? box.data?.on?.click;

  assert.ok(handler, 'Checkbox hat keinen change-/click-Handler');
  assert.equal(typeof optionen.methods.einwilligungGeaendert, 'function');

  const a = attrappen({ haken: true });
  optionen.methods.einwilligungGeaendert.call({ $refs: { einwilligung: a.einwilligung } });
  assert.deepEqual(a.aufrufe, [['setCustomValidity', '']]);
  assert.ok(vm);
});
