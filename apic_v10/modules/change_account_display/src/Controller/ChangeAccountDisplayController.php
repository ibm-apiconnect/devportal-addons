<?php

namespace Drupal\change_account_display\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for user routes.
 */
class ChangeAccountDisplayController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a UserController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(DateFormatterInterface $date_formatter, UserStorageInterface $user_storage, UserDataInterface $user_data, LoggerInterface $logger) {
    $this->dateFormatter = $date_formatter;
    $this->userStorage = $user_storage;
    $this->userData = $user_data;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('user.data'),
      $container->get('logger.factory')->get('user')
    );
  }



  /**
   * Route title callback.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   *
   * @return string|array
   *   The user 'firstname lastname' as a render array or an empty string if $user is
   *   NULL.
   */
  public function userTitle(UserInterface $user = NULL) {
    if (isset($user->get('first_name')->getValue()[0]['value']) && $user->get('first_name')->getValue()[0]['value'] !== NULL) {
      $markup = $user->get('first_name')->getValue()[0]['value'];
    }

    if (isset($user->get('last_name')->getValue()[0]['value']) && $user->get('last_name')->getValue()[0]['value'] !== NULL) {
      if (!empty($markup)) {
        $markup .= ' ';
      }
      $markup .= $user->get('last_name')->getValue()[0]['value'];
    }

    // if neither firstname nor last name were set then fall back on username
    if (!isset($markup) || empty($markup)) {
      $markup = $user->getUsername();
    }
    return $user ? ['#markup' => $markup, '#allowed_tags' => Xss::getHtmlTagList()] : '';
  }


}
