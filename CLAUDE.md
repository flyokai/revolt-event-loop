# flyokai/revolt-event-loop

> User docs → [`README.md`](README.md) · Agent quick-ref → [`CLAUDE.md`](CLAUDE.md) · Agent deep dive → [`AGENTS.md`](AGENTS.md)

Cooperative event loop for async PHP using Fibers. Fork replacing `revolt/event-loop`.

See [AGENTS.md](AGENTS.md) for detailed documentation.

## Quick Reference

- **Static API**: `EventLoop::defer()`, `delay()`, `repeat()`, `onReadable()`, `onWritable()`, `onSignal()`, `onMysqli()`
- **Suspension**: `getSuspension()` → `suspend()` / `resume()` / `throw()` — fiber pause/resume
- **FiberLocal**: Per-fiber isolated storage via WeakMap
- **Drivers**: UvDriver > EvDriver > EventDriver > StreamSelectDriver (auto-selected)
- **Callbacks**: Run in own Fiber, must return void/null
- **Key rule**: `run()` only from {main}; use Suspension API from fibers
