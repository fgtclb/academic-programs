<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Security\InlineEventHandlerMutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Type\Map;

/**
 * The selects of the sorting and filter form of the program list submit the form with
 * an inline `onchange` handler. A nonce does not cover an event handler attribute, so the
 * frontend policy TYPO3 ships blocks it once a site enforces it. This allows exactly that
 * handler text in `script-src-attr`, and nothing else.
 *
 * `script-src` stays as it is: `script-src-attr` applies to event handler attributes
 * only, and script elements keep their nonce. Where the policy of a site allows inline
 * scripts already, a listener of academic_base takes `script-src-attr` out again. An
 * override of a partial with another handler text needs the hash of that text in the
 * `csp.yaml` of the site.
 */
return Map::fromEntries([
    Scope::frontend(),
    new MutationCollection(
        new InlineEventHandlerMutation('this.form.submit()'),
    ),
]);
