# FORK notes — `flyokai/revolt-event-loop`

> User docs → [`README.md`](README.md) · Agent quick-ref → [`CLAUDE.md`](CLAUDE.md) · Agent deep dive → [`AGENTS.md`](AGENTS.md)

This package `replace`s upstream [`revolt/event-loop`](https://github.com/revoltphp/event-loop) and is consumed by every Flyokai project as the event-loop foundation under AMPHP 3.x.

## Upstream

- Repository: <https://github.com/revoltphp/event-loop>
- License: MIT (preserved in `LICENSE`)
- Original authors: Aaron Piotrowski, Cees-Jan Kiewiet, Christian Lück, Niklas Keller (see `LICENSE`)

## Why we forked

We needed the `EventLoop::onMysqli()` primitive to integrate `MYSQLI_ASYNC` into the cooperative event loop. Upstream's surface area is intentionally narrow — they did not want to take on a `mysqli`-specific binding.

`onMysqli()` is what powers [`flyokai/laminas-db-driver-async`](../laminas-db-driver-async/README.md)'s `AsyncMysqli` strategy.

## What changed

The fork is a thin extension of upstream:

- New static method: `EventLoop::onMysqli(\mysqli $link, \Closure $callback): string` — registers a callback that fires when the given `mysqli` connection running an async query is ready to be reaped.
- `Driver` and `DriverFactory` updated to surface `onMysqli` for every driver implementation.
- Everything else is upstream-equivalent.

## Compatibility

This fork **`replace`s** `revolt/event-loop` in `composer.json`. Other libraries declaring `require: revolt/event-loop` resolve to this fork. The public `EventLoop` API is a superset of upstream — code written against upstream Revolt runs unchanged.

## Tracking upstream

We rebase against upstream periodically (and on security advisories):

```bash
git remote add upstream https://github.com/revoltphp/event-loop.git
git fetch upstream
git rebase upstream/main
```

Conflicts are typically isolated to the `Driver` trait/abstract class where `onMysqli` was inlined. Resolution is mechanical.

## License

MIT — Copyright (c) 2021- Revolt (Aaron Piotrowski, Cees-Jan Kiewiet, Christian Lück, Niklas Keller, and contributors). See `LICENSE`. The fork adds no additional restrictions.
