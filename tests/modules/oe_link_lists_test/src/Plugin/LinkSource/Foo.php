<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists_test\Plugin\LinkSource;

use Drupal\oe_link_lists\Plugin\ExternalLinkSourcePluginBase;

/**
 * Plugin implementation of the link_source.
 *
 * @LinkSource(
 *   id = "foo",
 *   label = @Translation("Foo"),
 *   description = @Translation("Foo description.")
 * )
 */
class Foo extends ExternalLinkSourcePluginBase {}
