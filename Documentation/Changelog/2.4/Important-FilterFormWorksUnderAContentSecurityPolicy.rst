..  _important-ace-924-academic-programs:

========================================================
Important: The filter form works under a security policy
========================================================

Description
===========

The selects of the sorting and filter form of the :guilabel:`Program List`
content element submit the form when the visitor changes one of them. They do
that with an inline event handler, :html:`onchange="this.form.submit()"`. A
Content Security Policy that does not allow it blocks the handler, and that
includes the frontend policy of TYPO3 once a site enforces it, with the feature
:php:`security.frontend.enforceContentSecurityPolicy` for example: a nonce does
not cover an event handler attribute. Changing a select then did nothing, and
the form had no button to submit it otherwise.

The extension now ships :file:`Configuration/ContentSecurityPolicies.php`. It
extends the directive `script-src-attr` of the frontend policy by
`'unsafe-hashes'` and the hash of exactly that handler text,
`'sha256-osjxnKEPL/pQJbFk1dKsF7PYFmTyMWGmVSiL9inhxJY='`. TYPO3 loads the file
on its own and applies it to every site that inherits the default frontend
policy, which a site does unless its :file:`csp.yaml` sets
:yaml:`inheritDefault: false`. The directive `script-src`, which applies to
script elements, is not changed.

A policy that allows inline scripts already, with `'unsafe-inline'` in
`script-src`, or in `default-src` where it has no `script-src`, does not need
the hash. Where such a policy declares no `script-src-attr` of its own, an
event listener of :guilabel:`academic_base` takes the directive out again. The
browser then keeps reading `script-src` for event handler attributes, and the
handlers of the form follow the configuration of the site, as its own handlers
do.

The form also renders a submit button inside :html:`<noscript>`, labelled
:xml:`filter.submit`, for visitors without JavaScript. A visitor with
JavaScript sees the form as before.

Impact
======

*   On a site that enforces the frontend policy of TYPO3, changing a select
    sorts or filters the list again.
*   A site package that overrides :file:`Program/DemandSorting.html` or
    :file:`Program/DemandCategories.html` and keeps the handler text
    :html:`this.form.submit()` needs nothing. A copy with another handler text
    needs the hash of its own text, and a site with
    :yaml:`inheritDefault: false` needs this one, in the :file:`csp.yaml` of
    the site:

    ..  code-block:: yaml
        :caption: config/sites/<site>/csp.yaml

        mutations:
          - mode: extend
            directive: 'script-src-attr'
            sources:
              - "'unsafe-hashes'"
              - "'sha256-osjxnKEPL/pQJbFk1dKsF7PYFmTyMWGmVSiL9inhxJY='"

*   A copy of :file:`Program/SortingAndFilters.html` renders no button for
    visitors without JavaScript until it takes over the :html:`<noscript>`
    part of the shipped file.
*   A site that allows inline scripts with `'unsafe-inline'` keeps its own
    handlers working, the extension adds no `script-src-attr` there. On a page
    whose `script-src` keeps a nonce, a browser ignores `'unsafe-inline'` and
    blocks inline handlers, those of the site and those of the form alike.
*   A site that declares `script-src-attr` itself keeps it. Declared with
    `mode: set`, it replaces the hash of the extension. Declared with
    `mode: extend`, it carries the hash as well, unless it allows
    `'unsafe-inline'` itself: a browser ignores that next to a hash, so the
    listener takes the hash out again.
*   A browser reads `script-src-attr` for event handler attributes once a
    policy has it, and no longer `script-src`. A site that allows inline
    handlers of its own in `script-src` by their hashes adds those hashes to
    `script-src-attr`.
*   A policy that the web server or a proxy sends is not covered. The browser
    enforces it next to the one of TYPO3, or in its place, so it needs the
    same sources itself.
*   Behaviour is identical on every supported TYPO3 version.

.. index:: Frontend, Fluid, ext:academic_programs
