# flyokai/revolt-event-loop

> User docs → [`README.md`](README.md) · Agent quick-ref → [`CLAUDE.md`](CLAUDE.md) · Agent deep dive → [`AGENTS.md`](AGENTS.md)

> Rock-solid cooperative event loop for PHP 8.1+ — fork of [`revolt/event-loop`](https://github.com/revoltphp/event-loop) used as the foundation of every async operation in the Flyokai framework.

This package replaces `revolt/event-loop` (via Composer's `replace`) with a Flyokai-tracked fork that adds `EventLoop::onMysqli()` for the async-mysqli driver. The static API and semantics are otherwise identical to upstream.

> **Heads up.** Most users want upstream `revolt/event-loop`. This package only matters if you depend on Flyokai's `flyokai/laminas-db-driver-async`, which uses `onMysqli()`.

## Features

- **Static `EventLoop` accessor** — `defer`, `delay`, `repeat`, `onReadable`, `onWritable`, `onSignal`, `onMysqli`
- **`Suspension` API** — fiber pause/resume via `getSuspension()->suspend()/resume()/throw()`
- **`FiberLocal`** — per-fiber storage backed by `WeakMap`
- **Auto-selected driver** — `UvDriver` > `EvDriver` > `EventDriver` > `StreamSelectDriver`
- **`onMysqli()`** — Flyokai-specific addition: register a callback that fires when a `mysqli` connection running an async query is ready to be reaped

## Installation

```bash
composer require revolt/event-loop
```

`flyokai/revolt-event-loop` `replace`s `revolt/event-loop`, so the standard package name resolves to this fork inside Flyokai installs.

## Quick start

```php
use Revolt\EventLoop;

EventLoop::defer(function () {
    echo "next tick\n";
});

EventLoop::delay(0.5, function () {
    echo "after 500ms\n";
});

$id = EventLoop::repeat(1.0, function () {
    echo "tick\n";
});

EventLoop::run();   // call from {main} only
```

### Suspension from inside a fiber

```php
$suspension = EventLoop::getSuspension();

EventLoop::delay(0.1, fn() => $suspension->resume('done'));

$result = $suspension->suspend();   // 'done'
```

### Async mysqli (the Flyokai addition)

```php
$mysqli->query($sql, MYSQLI_ASYNC);

$suspension = EventLoop::getSuspension();

EventLoop::onMysqli($mysqli, function ($watcherId, mysqli $link) use ($suspension) {
    EventLoop::cancel($watcherId);
    $suspension->resume($link->reap_async_query());
});

$result = $suspension->suspend();
```

This is the primitive that `flyokai/laminas-db-driver-async`'s `AsyncMysqli` strategy is built on.

## Driver selection

Auto-selected by `DriverFactory` in priority order:

1. **`UvDriver`** — `ext-uv` (libuv). Highest performance.
2. **`EvDriver`** — `ext-ev` (libev).
3. **`EventDriver`** — `ext-event` (libevent). Cross-platform.
4. **`StreamSelectDriver`** — pure PHP `stream_select()`. Default fallback. Limited to ~1024 FDs.
5. **`TracingDriver`** — debug wrapper, set `REVOLT_DRIVER_DEBUG_TRACE=1`.

Override with `REVOLT_DRIVER=\Full\Class\Name`.

## Tick model

```
Activate queued callbacks
   → Execute defer callbacks
   → Dispatch one timer / signal / stream callback (each)
   → Continue while any referenced callbacks remain
```

Every callback runs in its own fiber. Callbacks must return `null` or `void` — anything else throws `InvalidCallbackError`.

## Gotchas

- **PHP version**: requires `>=8.1.17` or `>=8.2.4` (older versions have GC bugs). Override with `REVOLT_DRIVER_SUPPRESS_ISSUE_10496=1`.
- **Callback return**: must be `null`/`void`.
- **`run()` is `{main}` only**. From a fiber, use the `Suspension` API.
- **`fclose()` doesn't auto-cancel** — `cancel()` the callback explicitly.
- **Signals are process-global**. Same signal across drivers is undefined. Requires `ext-pcntl`.
- **`StreamSelectDriver` FD ceiling** — ~1024. Install `ext-uv`/`ext-ev`/`ext-event` to raise it.
- **WeakMap fiber refs** — circular references in user code can collect a fiber unexpectedly during GC.
- **Dead `{main}` suspension** — if the loop exits without resuming `{main}`, the suspension is permanently invalid.
- **Error handler must handle or re-throw** — exceptions inside the error handler halt the loop immediately.

## License

MIT — Copyright (c) 2021- Revolt (Aaron Piotrowski, Cees-Jan Kiewiet, Christian Lück, Niklas Keller, and contributors). See `LICENSE`.

## See also

- [`flyokai/laminas-db-driver-async`](../laminas-db-driver-async/README.md) — `AsyncMysqli` consumer of `onMysqli()`
- Upstream: <https://revolt.run> — for non-Flyokai projects, use `revolt/event-loop` directly.
