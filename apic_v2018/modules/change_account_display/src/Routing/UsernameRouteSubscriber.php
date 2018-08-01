<?php

namespace Drupal\change_account_display\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class UsernameRouteSubscriber extends RouteSubscriberBase {
  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // If we match certain routes then change the function that is used to generate the title to be
    // the one from our own controller
    if ($route = $collection->get('user.page')) {
      $route->setDefault('_title_callback', 'Drupal\change_account_display\Controller\ChangeAccountDisplayController::userTitle');
    }
    if ($route = $collection->get('entity.user.edit_form')) {
      $route->setDefault('_title_callback', 'Drupal\change_account_display\Controller\ChangeAccountDisplayController::userTitle');
    }
    if ($route = $collection->get('entity.user.canonical')) {
      $route->setDefault('_title_callback', 'Drupal\change_account_display\Controller\ChangeAccountDisplayController::userTitle');
    }
  }
}