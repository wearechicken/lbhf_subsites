<?php

declare(strict_types=1);

namespace Drupal\lbhf_subsites\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Subsite service.
 */
class SubsiteService {

  // Disable phpcs for a bit, so we don't have to add a load of stuff that's
  // made redundant by type hints.
  // phpcs:disable
  private EntityTypeManagerInterface $entityTypeManager;
  private MenuActiveTrailInterface $menuActiveTrail;
  private MenuLinkManagerInterface $menuLinkService;
  private ?NodeInterface $subsiteHomePage = null;
  private bool $searched = false;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    MenuActiveTrailInterface $menuActiveTrail,
    MenuLinkManagerInterface $menuLinkService
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->menuActiveTrail = $menuActiveTrail;
    $this->menuLinkService = $menuLinkService;
  }
  // phpcs:enable

  /**
   * Get the subsite homepage node if we're in a subsite.
   *
   * This will only call ::findHomePage() once per request, so it's fine to call
   * from multiple preprocess functions without a performance penalty.
   */
  public function getHomePage(): ?NodeInterface {

    if ($this->searched === FALSE) {
      $this->subsiteHomePage = $this->findHomePage();
      $this->searched = TRUE;
    }

    return $this->subsiteHomePage;
  }

  /**
   * Get the subsite homepage node if we're in a subsite.
   */
  private function findHomePage(): ?NodeInterface {

    $activeTrail = $this->menuActiveTrail->getActiveTrailIds(NULL);

    $node_ids = [];

    // Loop over the active trail and grab all the node IDs in it.
    foreach ($activeTrail as $menuLinkContentID) {

      // There's an empty string in the results for some reason.
      if (empty($menuLinkContentID)) {
        break;
      }

      $menuLink = $this->menuLinkService->createInstance($menuLinkContentID);
      $pluginDefinition = $menuLink->getPluginDefinition();

      if (!empty($pluginDefinition['route_parameters']['node'])) {
        $node_ids[] = $pluginDefinition['route_parameters']['node'];
      }
    }

    // Load the nodes we found.
    $nodes = $this->entityTypeManager
      ->getStorage('node')
      ->loadMultiple($node_ids);

    /** @var ?NodeInterface $subsiteHomePage */
    $subsiteHomePage = NULL;

    // Check each node in turn, until we find one of the right type.
    foreach ($nodes as $node) {
      if ($node->bundle() === 'lbhf_subsite_homepage') {
        $subsiteHomePage = $node;
        break;
      }
    }

    return $subsiteHomePage;
  }

}
