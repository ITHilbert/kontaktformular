# 0002 Middleware in `web` einhängen: Router + `afterResolving`, direkt nach `StartSession`
Status: gilt
Datum: 2026-10-26

## Entscheidung
`KontaktformularServiceProvider` setzt `HerkunftErfassen` am Router in die Gruppe `web` und in `middlewarePriority` direkt nach `StartSession`. Am HTTP-Kernel ergänzt er sie nur per `$this->app->afterResolving(...)`, also nur, wenn der Kernel erst nach dem Boot entsteht (Testbench). Nie `callAfterResolving` und nie `set…`/`append…` an einem bereits aufgelösten Kernel.

## Warum
- Direkt nach `StartSession`: sonst brechen CSRF (419), `auth` (302) oder `throttle` (429) den Request vorher ab, und der erste Request wird nicht erfasst (AK1).
- Nur Router + `afterResolving`: Im HTTP-Ablauf ist der Kernel schon vor dem Boot aufgelöst; schreibt er seine Gruppen neu auf den Router, verwirft er Middlewares anderer Pakete. Die erste Fassung verdrängte so `TwoFactorMiddleware` und `EnforcePasswordPolicy` von `userauth` aus `web`. Abgesichert durch `tests/Feature/WebMiddlewareIntegrationTest.php` im Host `packages_dev`; Regel im Skill `tech-laravel/package-entwicklung.md`, Abschnitt 5.

## Verworfene Alternativen
- `pushMiddlewareToGroup` ans Ende der Gruppe — läuft nach `auth`/`throttle`/CSRF.
- Globale Middleware — läuft vor `StartSession`, hat keine Session.
- Registrierung über `callAfterResolving` am Kernel — verwirft fremde Middlewares (s. o.).
