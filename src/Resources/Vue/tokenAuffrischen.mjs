// Holt vor dem Absenden ein frisches CSRF-Token und sendet das Formular genau einmal ab.
// Schlägt der Abruf fehl, wird mit dem vorhandenen Token abgesendet (Verhalten wie bisher).

export function erzeugeTokenAuffrischer({ formular, feld, url, fetchFn }) {
  let gestartet = false;

  async function tokenHolen() {
    try {
      const abruf = fetchFn ?? globalThis.fetch;
      const antwort = await abruf(url, {
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { Accept: 'application/json' },
      });
      if (!antwort || antwort.status !== 200) {
        return null;
      }
      const daten = await antwort.json();
      const token = daten?.token;
      return typeof token === 'string' && token !== '' ? token : null;
    } catch (fehler) {
      return null;
    }
  }

  async function absenden() {
    if (gestartet) {
      return;
    }
    gestartet = true;

    const token = await tokenHolen();
    if (token !== null) {
      feld.value = token;
    }
    formular.submit();
  }

  // Hebt die Sperre auf (Seite kommt per „Zurück“ aus dem Browser-Cache, JS-Zustand blieb erhalten).
  function zuruecksetzen() {
    gestartet = false;
  }

  return { absenden, zuruecksetzen };
}
