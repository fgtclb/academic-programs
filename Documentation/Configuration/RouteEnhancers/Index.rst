..  index:: Configuration; Route enhancers
..  _configuration-route-enhancers:

===============
Route enhancers
===============

This extension ships one ready made route enhancer in
:file:`Configuration/Yaml/Routes.yaml`. TYPO3 does not read that file on its
own — it is a fragment that has to be imported from the configuration of the
site which shows the plugin.

What the file enhances
----------------------

The file declares a single enhancer of type :yaml:`Extbase` named
:yaml:`AcademicPrograms`, bound to the plugin :yaml:`ProgramList` of the
extension :yaml:`AcademicPrograms`. That pair is what determines the argument
namespace the enhancer works on — :php:`tx_academicprograms_programlist`.

It registers one route for the :php:`list` action of
:php:`FGTCLB\AcademicPrograms\Controller\ProgramController`:

..  code-block:: yaml
    :caption: EXT:academic_programs/Configuration/Yaml/Routes.yaml

    routes:
      - routePath: '/{sorting_field}/{sorting_direction}'
        _controller: 'Program::list'
        _arguments:
          sorting_field: demand/sortingField
          sorting_direction: demand/sortingDirection

Both path variables are mapped by a :yaml:`StaticValueMapper`, so only the
values below ever appear in a URL, and anything else is rejected:

*   :yaml:`sorting_field` — the segment :yaml:`title` selects the demand value
    :yaml:`title`, :yaml:`last-updated` selects :yaml:`lastUpdated` and
    :yaml:`sorting` selects :yaml:`sorting`.
*   :yaml:`sorting_direction` — :yaml:`asc` and :yaml:`desc`.

Those are the values of
:php:`FGTCLB\AcademicPrograms\Enumeration\SortingOptions`, which is also what
the sorting select field of the plugin offers.

Both variables carry a default — :yaml:`title` and :yaml:`asc`. They are the
trailing segments of the route path, so a link that uses the default sorting
generates the plain page URL rather than :file:`/title/asc`.

Importing it into a site configuration
--------------------------------------

Add the resource to the :yaml:`imports` of the site that contains the page with
the program list plugin:

..  code-block:: yaml
    :caption: config/sites/my_site/config.yaml

    imports:
      - resource: 'EXT:academic_programs/Configuration/Yaml/Routes.yaml'

The import is merged into the site configuration, so an installation that
already defines other enhancers keeps them as long as no key is named
:yaml:`AcademicPrograms` twice. That is a statement about the merge and about
nothing else: distinct keys keep the entries, they do not keep two enhancers
from producing routes which match the same URL.

Limiting the enhancer to its page
---------------------------------

TYPO3 offers every enhancer declared in a site configuration to **every** page
of that site unless the enhancer says otherwise, and it takes the first
candidate route whose path matches *and* whose aspects resolve. The order the
candidates are tried in is the order of the :yaml:`imports`.

The route of this extension is comparatively hard to collide with, and that is
owed to its mappers rather than to its key: both path variables are handled by
a :yaml:`StaticValueMapper`, so a candidate is only accepted when the segments
are one of the three sorting fields followed by :yaml:`asc` or :yaml:`desc`.
Anything else is rejected and the next enhancer gets its turn. A route variable
that is mapped less narrowly — one whose aspect comes without an explicit
:yaml:`requirements` entry compiles to :yaml:`.+` and crosses slashes — has no
such protection, and even here a second extension mapping values of the same
spelling is enough to make the two compete.

:yaml:`limitToPages` settles it by naming the pages the enhancer applies to:

..  code-block:: yaml
    :caption: config/sites/my_site/config.yaml

    imports:
      - resource: 'EXT:academic_programs/Configuration/Yaml/Routes.yaml'

    routeEnhancers:
      AcademicPrograms:
        limitToPages: [23]

The uid is the one of the page carrying the list plugin, and it is the uid of
the **default language**: matching derives the page as :php:`l10n_parent ?: uid`,
so a single entry covers every translation of that page. Plain page uids work
on every TYPO3 version this extension supports.

In :guilabel:`academic_persons` the same mechanism is not a precaution but a
requirement — that extension ships three enhancers whose routes overlap each
other by construction. See `its route enhancer documentation
<https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Configuration/RouteEnhancers/Index.html>`__.

What the URLs look like
-----------------------

Assuming the plugin sits on a page with the slug :file:`/programs`, a link that
sorts by the last update, descending, is built without the enhancer as:

..  code-block:: text

    /programs?tx_academicprograms_programlist%5Bdemand%5D%5BsortingField%5D=lastUpdated&tx_academicprograms_programlist%5Bdemand%5D%5BsortingDirection%5D=desc

and with the enhancer imported as:

..  code-block:: text

    /programs/last-updated/desc

Caveats
-------

..  warning::

    The sorting and filter form shipped with the list plugin submits by
    **POST**. Its own requests therefore carry no arguments in the URL at all
    and are not enhanced — the address bar keeps showing the plain page URL
    after a submit. The enhancer takes effect for links that are built with the
    arguments as GET parameters, for example a ``f:link.action`` in an own
    template override or a hand written link.

Two further points are worth knowing:

*   The enhancer covers the list plugin only. The detail plugin
    (:yaml:`ProgramDetails`) takes no arguments — it renders the program of the
    page it sits on — so there is nothing to map into a path for it.
*   The two mappers are independent, which makes six combinations reachable
    while :php:`SortingOptions` only defines five. :file:`/sorting/desc`
    resolves although the plugin never offers it as an option.
