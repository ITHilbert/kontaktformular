// Tests für src/Resources/Vue/tokenAuffrischen.mjs (Dashboard #120, AK3, AK4, Randfall 1, Randfall 3).
// Lauf: node --test tests/js/tokenAuffrischen.test.mjs   (Node 22, ohne npm-Pakete)
//
// Schnittstelle des Moduls (vom Test festgelegt, der Coder baut danach):
//
//   export function erzeugeTokenAuffrischer({ formular, feld, url, fetchFn })
//     formular : Objekt mit submit()          (das <form>)
//     feld     : Objekt mit Eigenschaft value (das Eingabefeld `_token`)
//     url      : string, Adresse der Token-Route
//     fetchFn  : optional, Ersatz für globalThis.fetch (Vorgabe: globalThis.fetch)
//     Rückgabe : { absenden, zuruecksetzen }  mit  absenden(): Promise<void>
//
//   absenden() ruft fetchFn(url, { credentials: 'same-origin', cache: 'no-store', ... }) auf.
//   Antwortet die Route mit Status 200 und JSON { token: <nicht leerer String> }, wird das Token
//   in feld.value geschrieben. Danach (egal ob Abruf gelang oder nicht) genau ein formular.submit().
//   Das Promise wird nie abgelehnt. Ein zweiter Aufruf von absenden(), solange der erste läuft
//   oder bereits abgesendet wurde, tut nichts (kein zweiter Abruf, kein zweites submit()).
//
//   zuruecksetzen() hebt diese Sperre auf (Seite kommt per „Zurück“ aus dem Cache, pageshow mit
//   persisted): das nächste absenden() holt wieder ein Token und ruft genau ein submit() auf.

import assert from 'node:assert/strict';
import { test } from 'node:test';

import { erzeugeTokenAuffrischer } from '../../src/Resources/Vue/tokenAuffrischen.mjs';

const URL_TOKEN = '/kontaktformular/csrf-token';
const ALT = 'altes-token';
const NEU = 'frisches-token';

function antwort({ status = 200, json } = {}) {
  return {
    status,
    ok: status >= 200 && status < 300,
    json: typeof json === 'function' ? json : async () => json,
  };
}

function baue({ fetchFn, url = URL_TOKEN } = {}) {
  const log = [];
  const aufrufe = [];
  const feld = {
    _wert: ALT,
    get value() {
      return this._wert;
    },
    set value(v) {
      this._wert = v;
      log.push(`feld:${v}`);
    },
  };
  const formular = {
    submits: 0,
    wertBeimSubmit: [],
    submit() {
      this.submits += 1;
      this.wertBeimSubmit.push(feld.value);
      log.push('submit');
    },
  };
  const fetchMitLog = (u, opts) => {
    log.push('fetch');
    aufrufe.push({ url: u, opts });
    return fetchFn(u, opts);
  };
  const absender = erzeugeTokenAuffrischer({ formular, feld, url, fetchFn: fetchMitLog });

  return { absender, formular, feld, log, aufrufe };
}

const erfolg = () => async () => antwort({ json: { token: NEU } });

// AK3
test('AK3: erst Abruf, dann Token im Feld, dann genau ein submit', async () => {
  const t = baue({ fetchFn: erfolg() });

  await t.absender.absenden();

  assert.deepEqual(t.log, ['fetch', `feld:${NEU}`, 'submit']);
  assert.equal(t.formular.submits, 1);
  assert.deepEqual(t.formular.wertBeimSubmit, [NEU]);
  assert.equal(t.feld.value, NEU);
});

// AK3
test('AK3: das Formular wird nicht abgesendet, bevor der Abruf beantwortet ist', async () => {
  let antworten;
  const t = baue({ fetchFn: () => new Promise((r) => (antworten = r)) });

  const laeuft = t.absender.absenden();
  await new Promise((r) => setImmediate(r));

  // Gegenprobe: der Abruf ist raus, das Feld ist aber noch unverändert
  assert.equal(t.aufrufe.length, 1);
  assert.equal(t.formular.submits, 0);
  assert.equal(t.feld.value, ALT);

  antworten(antwort({ json: { token: NEU } }));
  await laeuft;

  assert.equal(t.formular.submits, 1);
  assert.deepEqual(t.formular.wertBeimSubmit, [NEU]);
});

// AK3
test('AK3: Abruf an die übergebene URL, gleiche Herkunft, ohne Cache', async () => {
  const t = baue({ fetchFn: erfolg(), url: '/anderer/pfad/token' });

  await t.absender.absenden();

  assert.equal(t.aufrufe.length, 1);
  assert.equal(t.aufrufe[0].url, '/anderer/pfad/token');
  assert.equal(t.aufrufe[0].opts.credentials, 'same-origin');
  assert.equal(t.aufrufe[0].opts.cache, 'no-store');
  assert.equal(t.formular.submits, 1);
});

// AK3
test('AK3: ohne übergebenes fetchFn wird globalThis.fetch benutzt', async () => {
  const original = globalThis.fetch;
  const aufrufe = [];
  globalThis.fetch = async (u, opts) => {
    aufrufe.push({ u, opts });
    return antwort({ json: { token: NEU } });
  };
  try {
    const feld = { value: ALT };
    const formular = { submits: 0, submit() { this.submits += 1; } };
    const a = erzeugeTokenAuffrischer({ formular, feld, url: URL_TOKEN });

    await a.absenden();

    assert.equal(aufrufe.length, 1);
    assert.equal(aufrufe[0].u, URL_TOKEN);
    assert.equal(feld.value, NEU);
    assert.equal(formular.submits, 1);
  } finally {
    globalThis.fetch = original;
  }
});

// AK4
const FEHLERFAELLE = {
  'Netzfehler (fetch lehnt ab)': async () => {
    throw new TypeError('Failed to fetch');
  },
  'fetch wirft sofort': () => {
    throw new Error('kaputt');
  },
  'Status 404': async () => antwort({ status: 404, json: { token: NEU } }),
  'Status 419': async () => antwort({ status: 419, json: { token: NEU } }),
  'Status 429': async () => antwort({ status: 429, json: { token: NEU } }),
  'Status 500': async () => antwort({ status: 500, json: { token: NEU } }),
  'Status 204 (nicht 200)': async () => antwort({ status: 204, json: { token: NEU } }),
  'Status 302': async () => antwort({ status: 302, json: { token: NEU } }),
  'Token leer': async () => antwort({ json: { token: '' } }),
  'Token kein String (Zahl)': async () => antwort({ json: { token: 123 } }),
  'Token kein String (Objekt)': async () => antwort({ json: { token: { a: 1 } } }),
  'Token kein String (Array)': async () => antwort({ json: { token: [NEU] } }),
  'Token null': async () => antwort({ json: { token: null } }),
  'Token fehlt im JSON': async () => antwort({ json: { anders: NEU } }),
  'JSON ist null': async () => antwort({ json: null }),
  'JSON ist ein String': async () => antwort({ json: NEU }),
  'kein JSON (json() wirft)': async () =>
    antwort({
      json: async () => {
        throw new SyntaxError('Unexpected token < in JSON');
      },
    }),
};

for (const [name, fetchFn] of Object.entries(FEHLERFAELLE)) {
  // AK4
  test(`AK4: ${name} -> genau ein submit mit vorhandenem Token, ohne Fehler`, async () => {
    const t = baue({ fetchFn });

    await assert.doesNotReject(t.absender.absenden());

    // Gegenprobe: der Abruf wurde versucht (der Fehlerpfad wurde wirklich durchlaufen)
    assert.equal(t.aufrufe.length, 1);
    assert.equal(t.formular.submits, 1);
    assert.deepEqual(t.formular.wertBeimSubmit, [ALT]);
    assert.equal(t.feld.value, ALT);
    assert.deepEqual(t.log, ['fetch', 'submit']);
  });
}

// Randfall 1
test('Randfall 1: Doppelklick während des Abrufs -> genau ein Abruf und ein submit', async () => {
  let antworten;
  const t = baue({ fetchFn: () => new Promise((r) => (antworten = r)) });

  const erster = t.absender.absenden();
  const zweiter = t.absender.absenden();
  const dritter = t.absender.absenden();
  await new Promise((r) => setImmediate(r));

  // Gegenprobe: der erste Klick ist wirklich unterwegs
  assert.equal(t.aufrufe.length, 1);
  assert.equal(t.formular.submits, 0);

  antworten(antwort({ json: { token: NEU } }));
  await Promise.all([erster, zweiter, dritter]);

  assert.equal(t.aufrufe.length, 1);
  assert.equal(t.formular.submits, 1);
  assert.deepEqual(t.formular.wertBeimSubmit, [NEU]);
});

// Randfall 1
test('Randfall 1: Doppelklick bei fehlgeschlagenem Abruf -> genau ein submit', async () => {
  let ablehnen;
  const t = baue({ fetchFn: () => new Promise((_, rej) => (ablehnen = rej)) });

  const erster = t.absender.absenden();
  const zweiter = t.absender.absenden();
  await new Promise((r) => setImmediate(r));
  assert.equal(t.aufrufe.length, 1);

  ablehnen(new TypeError('Failed to fetch'));
  await Promise.all([erster, zweiter]);

  assert.equal(t.aufrufe.length, 1);
  assert.equal(t.formular.submits, 1);
  assert.deepEqual(t.formular.wertBeimSubmit, [ALT]);
});

// Randfall 1
test('Randfall 1: erneutes Absenden nach abgeschlossenem Absenden sendet nicht noch einmal', async () => {
  const t = baue({ fetchFn: erfolg() });

  await t.absender.absenden();
  assert.equal(t.formular.submits, 1);

  await t.absender.absenden();

  assert.equal(t.aufrufe.length, 1);
  assert.equal(t.formular.submits, 1);
});

// Randfall 3
test('Randfall 3: nach zuruecksetzen() holt das nächste absenden() erneut ein Token und sendet genau einmal', async () => {
  let n = 0;
  const t = baue({ fetchFn: async () => antwort({ json: { token: `token-${++n}` } }) });

  await t.absender.absenden();
  assert.equal(t.aufrufe.length, 1);
  assert.equal(t.formular.submits, 1);

  // Gegenprobe: ohne Zurücksetzen bleibt das zweite absenden() wirkungslos (Randfall 1)
  await t.absender.absenden();
  assert.equal(t.aufrufe.length, 1);
  assert.equal(t.formular.submits, 1);

  t.absender.zuruecksetzen();
  await t.absender.absenden();

  assert.equal(t.aufrufe.length, 2);
  assert.equal(t.feld.value, 'token-2');
  assert.equal(t.formular.submits, 2);
  assert.deepEqual(t.formular.wertBeimSubmit, ['token-1', 'token-2']);
});

// Randfall 3
test('Randfall 3: nach zuruecksetzen() gilt die Sperre gegen Doppelklick wieder (genau ein weiterer Abruf und submit)', async () => {
  const t = baue({ fetchFn: erfolg() });

  await t.absender.absenden();
  t.absender.zuruecksetzen();

  await Promise.all([t.absender.absenden(), t.absender.absenden()]);

  assert.equal(t.aufrufe.length, 2);
  assert.equal(t.formular.submits, 2);
});
