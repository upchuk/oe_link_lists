<?php

declare(strict_types = 1);

namespace Drupal\oe_link_lists;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * DisplayOptions plugin manager.
 */
class LinkListDisplayOptionsPluginManager extends DefaultPluginManager implements LinkListDisplayOptionsPluginManagerInterface {

  /**
   * Constructs LinkListDisplayOptionsPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/LinkListDisplayOptions',
      $namespaces,
      $module_handler,
      'Drupal\oe_link_lists\LinkListDisplayOptionsPluginInterface',
      'Drupal\oe_link_lists\Annotation\LinkListDisplayOptions'
    );
    $this->alterInfo('link_list_display_options_info');
    $this->setCacheBackend($cache_backend, 'link_list_display_options_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getApplicablePlugin(string $bundle): string {
    $definitions = $this->getDefinitions();
    $applicable_plugins = [];
    foreach ($definitions as $name => $definition) {
      if ($bundle === $definition['bundle']) {
        $applicable_plugins[$name] = $definition['priority'];
      }
    }
    arsort($applicable_plugins);
    reset($applicable_plugins);
    return key($applicable_plugins);
  }

}
