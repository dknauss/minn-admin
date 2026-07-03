# Extending Minn Admin to other plugins (proposal)

How does Minn surface Gravity Forms entries, Gravity SMTP logs, or any other plugin's data —
without hand-building a UI for every plugin in the ecosystem?

## The three possible strategies

1. **Bespoke views per plugin** — what Orders is for WooCommerce. Highest quality, highest cost.
   Only justifiable for a handful of high-value plugins.
2. **Iframe the plugin's wp-admin pages.** Works for anything, looks like wp-admin stuffed inside
   Minn. Rejected — it reintroduces exactly the chrome Minn removes.
3. **A declarative "surface" API** — plugins (or Minn-bundled adapters) *describe* their data and
   Minn renders it with the generic primitives that already exist (stat cards, tables with tabs,
   detail modals, row actions, Load-more pagination). This is the scalable path.

## Recommendation: descriptor-driven surfaces, with bespoke as the escalation

Most plugin admin screens are one of three shapes: a **list** (form entries, SMTP logs, orders,
submissions), a **detail** view of one item, and a few **stat numbers**. Minn already renders all
three shapes generically — Orders/Users/Comments are hand-wired instances of them. The proposal is
to make that wiring data-driven:

```php
add_filter( 'minn_admin_surfaces', function ( $surfaces ) {
    $surfaces['gravity-forms'] = [
        'label'  => 'Forms',
        'icon'   => 'inbox',
        'cap'    => 'gravityforms_view_entries',
        'stats'  => [ /* optional stat-card descriptors */ ],
        'collection' => [
            'route'   => 'gf/v2/entries',           // any REST route, called with cookie+nonce
            'tabs'    => [ 'form_id' => 'gf/v2/forms' ],  // tab list fed by another route
            'columns' => [
                [ 'key' => 'date_created', 'label' => 'Date',   'format' => 'ago' ],
                [ 'key' => '1',            'label' => 'Name' ],   // GF field IDs are keys
                [ 'key' => 'status',       'label' => 'Status', 'format' => 'pill' ],
            ],
            'detail'  => [ 'route' => 'gf/v2/entries/{id}' ],   // rows → detail modal
            'actions' => [
                [ 'label' => 'Spam',  'method' => 'PUT', 'route' => 'gf/v2/entries/{id}', 'body' => [ 'status' => 'spam' ], 'confirm' => false ],
                [ 'label' => 'Trash', 'method' => 'DELETE', 'route' => 'gf/v2/entries/{id}', 'confirm' => true ],
            ],
        ],
    ];
    return $surfaces;
} );
```

Minn's PHP passes registered surfaces into the boot payload (filtered by capability); the JS adds
a nav item per surface and renders it entirely from the descriptor. **No plugin needs to ship
JavaScript**, and third-party plugins can integrate without knowing anything about Minn's
internals — it's one filter.

### Two registration paths

- **Native**: a plugin hooks `minn_admin_surfaces` itself (the long-term ecosystem play).
- **Bundled adapters**: Minn ships descriptors for popular plugins that will never know about it
  (`includes/adapters/gravity-forms.php`, registered only when `class_exists( 'GFAPI' )`). This is
  how coverage grows immediately without waiting for anyone.

### When a descriptor isn't enough

The escalation ladder: **descriptor surface → bespoke JS view → "Open in wp-admin" link.**
WooCommerce Orders stays bespoke (summary cards + currency handling earn it). A plugin with no
REST API at all needs a small PHP shim first — a `minn-admin/v1/proxy/<surface>` endpoint the
adapter registers server-side (e.g. Gravity SMTP's email log lives in custom tables; the adapter
would query them directly and expose a REST collection Minn can consume).

## Reality check on the two named plugins

- **Gravity Forms** ships a real REST API (`gf/v2/forms`, `gf/v2/entries`) with cookie-auth
  support and per-cap permissions — a bundled adapter is buildable today with list/detail/spam/
  trash actions. Good first proof of the descriptor format.
- **Gravity SMTP** stores its email log in custom tables and drives its React UI through internal
  endpoints — no stable public REST surface. It's the motivating case for the PHP-shim adapter:
  ~40 lines of SQL-to-REST in `includes/adapters/gravity-smtp.php`, then the generic descriptor
  renders the log (subject, to, status, opened) with a detail modal showing the message body.

## Suggested build order

1. Extract the generic collection renderer from Orders/Users into a descriptor interpreter.
2. Boot-payload plumbing: `minn_admin_surfaces` filter → capability filter → `window.MINN.surfaces`.
3. Bundled Gravity Forms adapter (pure descriptor, no shim) — proves the format.
4. Bundled Gravity SMTP adapter (descriptor + REST shim) — proves the shim pattern.
5. Document the filter publicly; that's the ecosystem invitation.
