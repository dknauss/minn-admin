# Editor direction

**Decision: keep and deepen Minn's own editor. Use Gutenberg as the escape hatch, not the foundation.**

## The options considered

1. **Embed the block editor** (`@wordpress/edit-post` or an iframe of `post.php`). Full block
   fidelity, but it drags in React, the build toolchain, and the exact visual noise Minn exists to
   remove. An iframed wp-admin inside Minn is two admins fighting in one window.
2. **Rebuild block support piecemeal in the custom editor.** Chasing full parity with Gutenberg
   (nested layouts, patterns, dynamic blocks) is a treadmill we would never get off.
3. **Hybrid (chosen).** Minn's editor owns the *writing* use case — the 90% of edits that are
   paragraphs, headings, lists, quotes, code and images. It reads and writes native Gutenberg
   block markup for that subset, so nothing is proprietary and every post remains fully editable
   in Gutenberg at any time. Anything beyond the safe subset (`SIMPLE_BLOCKS` in `app.js`) locks
   the body read-only and hands off to the real block editor with one click.

## Why hybrid wins

- **Interop is guaranteed by the storage format.** Minn writes `<!-- wp:paragraph -->`-style
  markup that `parse_blocks()` validates. There is no lock-in and no migration.
- **The lock is the safety valve.** `editorModeFor()` classifies content as `classic` / `blocks` /
  `locked`. Locked posts never have their content sent on save, so a complex layout can't be
  damaged by Minn — worst case you click through to Gutenberg.
- **No build step.** The whole app stays a single vanilla-JS file, which is the plugin's core
  architectural bet.

## Where the line moves over time

Grow `SIMPLE_BLOCKS` deliberately, one block at a time, only when the contenteditable round-trip
is proven safe (galleries and embeds are the likely next candidates). Never grow it to chase
parity — if a site's content is mostly complex layouts, Gutenberg is simply the right tool and
Minn should be great at everything *around* the editor.
