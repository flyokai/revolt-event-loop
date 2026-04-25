# flyokai/revolt-event-loop

> User docs → [`README.md`](README.md) · Agent quick-ref → [`CLAUDE.md`](CLAUDE.md) · Agent deep dive → [`AGENTS.md`](AGENTS.md)

Cooperative multitasking event loop for PHP 8.1+ using native Fibers. Fork of `revolt/event-loop` (replaces it via composer). Foundation for all async operations in the Flyokai ecosystem.

## Core Architecture

### EventLoop (static accessor)

Global singleton delegating to the underlying `Driver`. All operations are static:
- `run()` — start the event loop (only from {main}, not fibers)
- `queue(Closure)` — microtask, executes before next tick
- `defer(Closure)` — execute in next tick
- `delay(float $seconds, Closure)` — one-time delayed execution
- `repeat(float $interval, Closure)` — recurring execution
- `onReadable($stream, Closure)` / `onWritable($stream, Closure)` — I/O monitoring
- `onSignal(int $signal, Closure)` — POSIX signal handling (requires ext-pcntl)
- `onMysqli(mysqli, Closure)` — async MySQLi support
- `enable($id)` / `disable($id)` / `cancel($id)` — callback lifecycle
- `reference($id)` / `unreference($id)` — keep loop alive / allow exit
- `getSuspension(): Suspension` — pause/resume current fiber
- `setErrorHandler(?Closure)` — global error callback

### Driver Implementations

Auto-selected by `DriverFactory` in priority order:

1. **UvDriver** — `ext-uv` (libuv). Highest performance.
2. **EvDriver** — `ext-ev` (libev). High performance.
3. **EventDriver** — `ext-event` (libevent). Cross-platform.
4. **StreamSelectDriver** — pure PHP `stream_select()`. Default fallback. Limited to ~1024 FDs.
5. **TracingDriver** — debug wrapper, activated via `REVOLT_DRIVER_DEBUG_TRACE=1`

Custom driver: set `REVOLT_DRIVER=\Full\Class\Name` environment variable.

### Callback Types (enum)

- `Defer` — microtask, next tick
- `Delay` — one-time after interval
- `Repeat` — recurring at interval
- `Readable` / `Writable` — stream I/O
- `Signal` — POSIX signal

### Suspension API

Preferred way to pause/resume fibers:

```php
$suspension = EventLoop::getSuspension();
// In some callback:
$suspension->resume($value);  // or $suspension->throw($exception)
// Current fiber:
$result = $suspension->suspend();  // blocks until resume/throw
```

One suspension per fiber. `DriverSuspension` uses `WeakMap` for fiber association.

### FiberLocal

Fiber-local storage using `WeakMap`. Each fiber gets isolated data:

```php
$local = new FiberLocal(fn() => 'default');
$local->get();  // 'default' for this fiber
$local->set('value');  // only this fiber sees it
```

### Tick Execution Model

One tick:
1. **Activate** queued callbacks
2. **Execute** all defer callbacks
3. **Dispatch** timer, signal, and stream callbacks (once each per tick)
4. **Continue** until stopped or no referenced callbacks remain

Each callback runs in its own Fiber. Callbacks must return void/null.

### Timer Management

`TimerQueue` — binary min-heap by expiration time. O(log n) insert/extract. Used by all drivers.

## Gotchas

- **PHP version**: Requires >=8.1.17 or >=8.2.4 (GC bugs in earlier versions). Override check with `REVOLT_DRIVER_SUPPRESS_ISSUE_10496`.
- **Callback return values**: Must return null/void. Non-null throws `InvalidCallbackError`.
- **`run()` only from {main}**: Cannot call from within a fiber. Use `Suspension` API from fibers.
- **Stream closure**: `fclose()` doesn't trigger callbacks. Must explicitly `cancel()` the callback ID.
- **Signal limitations**: Process-global. Same signal on different drivers is undefined. Requires ext-pcntl (not on Windows).
- **FD_SETSIZE limit**: `StreamSelectDriver` limited to ~1024 concurrent connections. Use ext-uv/ev/event for more.
- **WeakMap suspensions**: Fiber references are weak. Circular references in user code can cause fiber destruction during GC.
- **Dead {main} suspension**: If loop exits without resuming {main}, suspension becomes permanently invalid.
- **Error handler**: Must handle or re-throw. Exceptions in error handler halt the loop immediately.
