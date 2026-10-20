# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`mammothwp/wp-kit` — a dependency-free PHP 7.4+ library (namespace `MammothWP\WpKit`, hook prefix `mmwp_kit_`, error-code prefix `mmwp_`). A consumer declares a WordPress feature once as a `Resource` of `Operation`s with `Field`s, and the three adapters project it onto REST routes, WP-CLI commands and the WordPress Abilities API. Source of truth is GitLab `mammothwp/wp-kit`; `https://github.com/mammothwp/wp-kit` is a push mirror and Packagist reads it. Public identity is Mammoth WP <info@mammothwp.com>, https://mammothwp.com; brand rules are in `docs/BRAND.md` (prefixes `mmwp_`/`mmwp-`, never the word "WordPress" in a name or prefix). Use `my-plugin` in examples.

PHPUnit tests live in `tests/` (fake hook API in `tests/stubs.php`); PHPCS (WPCS prefixes `mmwp`/`MammothWP`) and PHPStan run from `phpcs.xml.dist` / `phpstan.neon.dist`. `.github/workflows/lint.yml` runs `php -l`, `composer validate --strict`, PHPUnit, PHPCS and PHPStan.

## Commands

Run what CI runs (`composer lint|test|phpcs|phpstan`):

```bash
git ls-files -z -- "*.php" | xargs -0 -n1 php -l
```

Validate composer.json as CI does:

```bash
composer validate --strict --no-check-lock
```

Everything in `src/` is guarded by `if (!defined('ABSPATH')) exit;` and `bootstrap.php` returns early outside WordPress, so nothing here can be executed standalone; tests define `ABSPATH` and stub the hook API. Wider behavioural verification happens in a consumer plugin's parity tests.

## Release checklist

Version lives in two places that must be bumped together: `version.php` (the runtime value read by `bootstrap.php`) and `CHANGELOG.md`. `composer.json` carries no `version` field and nobody creates tags here. New public methods get `@since <version>`.

## Architecture

### Loading is version-gated, not Composer-driven

`composer.json` autoloads only `bootstrap.php` (`autoload.files`); the PSR-4 map is `autoload-dev` only, on purpose. Several active plugins may each ship their own `vendor/mammothwp/wp-kit`; every copy calls `Kit::register($version, $dir)`, and on `plugins_loaded` priority -100 `Kit::boot()` picks the highest version, installs *that* copy's `spl_autoload` for `MammothWP\WpKit\*` and flushes `Kit::on_ready()` callbacks. Consequences:

- `src/Kit.php` is loaded by whichever plugin includes it first, even if a newer copy later wins the autoloader. Its public API must stay backwards compatible forever; never remove or change the signature of a `Kit` method.
- Consumers must do all kit work inside `Kit::on_ready()` — nothing in `MammothWP\WpKit` is autoloadable before boot.
- A copy registered after `plugins_loaded` has already fired boots immediately. A copy registered before `wp-includes/plugin.php` exists (Composer `autoload.files` run from `wp-config.php` — Bedrock root installs, every WP-CLI command) pre-seeds `$wp_filter['plugins_loaded'][-100]` instead of booting: never treat a missing `add_action()` as "too late" (0.3.2). The first `register()` decides the boot timing; later copies only join the version race if boot hasn't happened yet.

### Declaration → execution → projection

```
Resource (abstract, one per feature)
  ├─ name(), service(), operations()           required
  ├─ rest_namespace()/cli_base()/ability_category()  null = surface disabled for whole resource
  ├─ capability()                              default resolver ('manage_options')
  └─ surfaces()/surfaces_reason()              default surface set (0.2.0)
        │  ops() binds each Operation (attach name+resource, apply_defaults) and memoises
        ▼
Operation (final, fluent) ── read()/write()/destructive() factories
  ├─ input([Field...]), capability(), confirm(), surfaces()
  ├─ rest(method,path), cli(name,opts), ability(name)   per-surface bindings
  └─ run(array $input, Context $ctx)           THE single execution path
        │
        ▼
Adapters/{Rest,Cli,Ability}_Adapter  ── translate transport in/out around run()
Field_Map                            ── one Field → REST args / CLI synopsis / JSON Schema
Inventory::export(Registry)          ── flat rows for parity tests (surfaces + excluded + why)
```

Rules that follow from this shape:

- **Adapters never contain business rules.** Validation, authorisation, confirmation and the `mmwp_kit_operation_*` hooks live in `Operation::run()` (normalise → validate → `Capability` → `Confirm` → before-run action → `invoke()` → result filter). If a behaviour would differ per surface, it belongs in `Context` (`surface()`, `is_unattended_cli()`), not in an adapter.
- **A parameter is declared once.** Add field types/modifiers in `Field` and project them in `Field_Map` for all three surfaces in the same change; the README's "What one field becomes" table is the contract.
- **Advertised ⇒ enforced.** Any constraint a field projects into REST args or the ability schema must also be checked in `Field` validation, including on nested `Field::object()->properties([...])` children (0.3.0 fixed constraints that were advertised but never validated). Adding a modifier to `Field_Map` without validation is a security bug.
- **`Field` is an immutable value object** (setters return clones); `Operation` is mutable and bound to exactly one `Resource` via `attach()`.
- **Surface opt-out has two levels.** `Operation::surfaces()` (sets `surfaces_set`) beats `Resource::surfaces()`; both accept a callable resolved lazily (CLI adapter registers on `init` priority 5 for this reason). Any exclusion should carry a reason so `Inventory` can distinguish deliberate omission from a missing registration.
- **Service call mapping**: `invoke()` matches service method parameters by name via reflection — but only for declared fields (and `confirm`); undeclared keys admitted by `->allow_extra()` never bind by name and go to the first `array`-typed parameter; `->args(callable)` overrides.
- **Handler exceptions become `mmwp_operation_failed` (500) with a generic message.** The real exception is `error_log`ged and exposed as `detail` only on the CLI surface or with `WP_DEBUG`; never put exception text back into REST/ability responses.
- **Unattended WP-CLI (no `--user`) is trusted as admin** — `Capability::check()` short-circuits on `Context::is_unattended_cli()`. Don't "fix" this.
- **Destructive ops default to requiring `confirm: true` on the Ability surface only**; CLI maps `--yes` onto the same field. `Confirm::check()` coerces the flag like a `bool` field, so `"no"`/`"off"`/arbitrary strings do not confirm.

### Hooks

All custom hooks are `mmwp_kit_`-prefixed and documented WP-core style above each `do_action`/`apply_filters`. `HOOKS.md` is the authoritative declaration (name, type, params, where fired) and the README "Hooks" table is a summary of it — update both when adding or changing a hook. Error codes use the `mmwp_` prefix: `mmwp_invalid_param`, `mmwp_forbidden` (401 anonymous / 403 authenticated), `mmwp_confirm_required`, `mmwp_no_handler`, `mmwp_operation_failed`.

## Conventions specific to this repo

- PHP 7.4 compatible: no `readonly`, enums, `match`, named args or union types in `src/`. Typed properties are avoided in favour of `@var` docblocks.
- WordPress-style naming: `Snake_Case` classes, `snake_case` methods, 4-space indent, one class per file at `src/<Class>.php` (the loader's autoloader maps `MammothWP\WpKit\Adapters\X` → `src/Adapters/X.php`).
- Every file, class and public method has a docblock with `@since`; every file starts with the `ABSPATH` guard.
- WordPress functions are always guarded with `function_exists()`/`class_exists()` (`do_action`, `apply_filters`, `WP_CLI`, `register_rest_route`, `wp_register_ability`) so the classes can be constructed in isolation.
- `CHANGELOG.md` entries record the originating prompt/intent and a "Backwards compatibility" note when relevant.

## Compat layer (removed in 2.0.0)

`compat.php` (Composer `files`) registers `Compat\Legacy_Aliases`, a lazy autoloader that `class_alias()`es the 0.x `Gnnpls\WP\*` names to the new classes and calls `_deprecated_class()` once per class. `Compat\Legacy_Hooks` fires the old `mwp_*` hooks after each new one through `do_action_deprecated()`/`apply_filters_deprecated()`. A new public class must be added to `Legacy_Aliases::CLASSES` only if it existed in 0.x; new hooks need no legacy twin. The `gnnpls`/`mwp_` strings are allowed only in `src/Compat/`, composer `replace`, tests of the shims and the CHANGELOG.
