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

Neither variable carries a default, on purpose: a link that uses the default
sorting generates :file:`/title/asc`, not the plain page URL. The plain page URL
is where the sorting configured in the content element applies, so a default
would turn a link asking for the title, ascending, into a link to whatever the
element is configured with. The same holds for an enhancer a site writes for
this plugin itself. A path with the field alone, such as
:file:`/last-updated`, does not resolve.

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
sorts by the last update, descending, is built without the enhancer as (line
breaks added, and the brackets not encoded, for reading):

..  code-block:: text

    /programs?tx_academicprograms_programlist[action]=list
        &tx_academicprograms_programlist[controller]=Program
        &tx_academicprograms_programlist[demand][sortingDirection]=desc
        &tx_academicprograms_programlist[demand][sortingField]=lastUpdated
        &cHash=…

and with the enhancer imported as:

..  code-block:: text

    /programs/last-updated/desc

Caveats
-------

..  warning::

    The sorting and filter form shipped with the list plugin submits by
    **POST** to a URL without any sorting. That URL does not enter the route,
    so its requests are not enhanced — after a submit the address bar shows
    the page URL with the plugin's action, controller and cache hash in the
    query string. The enhancer takes effect for links that are built with the
    sorting as GET parameters, for example a ``f:link.action`` in an own
    template override or a hand written link.

Two further points are worth knowing:

*   The enhancer covers the list plugin only. The detail plugin
    (:yaml:`ProgramDetails`) takes no arguments — it renders the program of the
    page it sits on — so there is nothing to map into a path for it.
*   The two mappers are independent, so every one of the six combinations they
    can spell is reachable — and :php:`SortingOptions` defines exactly those
    six, so each path the enhancer resolves is an ordering the plugin really
    renders. Until the reversed page sorting was added, :file:`/sorting/desc`
    resolved to an option that did not exist and was silently dropped.
