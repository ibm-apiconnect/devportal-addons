<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apic_secure_auth\Form;


use Drupal\auth_apic\Service\Interfaces\OidcRegistryServiceInterface;
use Drupal\auth_apic\UserManagement\ApicInvitationInterface;
use Drupal\auth_apic\UserManagement\ApicLoginServiceInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\BareHtmlPageRendererInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\ApicType\UserRegistry;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\UserManagement\ApicAccountInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\user\Entity\User;
use Drupal\user\Form\UserLoginForm;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\auth_apic\Service\Interfaces\TokenParserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

enum InputForm {
  case EmailInputForm;
  case CredentialsInputForm;
}

class ApicUserLoginForm extends UserLoginForm {

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * @var \Drupal\ibm_apim\UserManagement\ApicAccountInterface
   */
  protected ApicAccountInterface $accountService;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface
   */
  protected UserRegistryServiceInterface $userRegistryService;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  protected ApimUtils $apimUtils;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  protected SiteConfig $siteConfig;

  /**
   * @var \Drupal\auth_apic\Service\Interfaces\OidcRegistryServiceInterface
   */
  protected OidcRegistryServiceInterface $oidcService;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected PrivateTempStore $authApicSessionStore;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected Config $ibmSettingsConfig;

  /**
   * @var \Drupal\auth_apic\UserManagement\ApicLoginServiceInterface
   */
  protected ApicLoginServiceInterface $loginService;

  /**
   * @var \Drupal\auth_apic\UserManagement\ApicInvitationInterface
   */
  protected ApicInvitationInterface $invitationService;

  /**
   * @var \Drupal\user\UserFloodControl
   */
  protected $userFloodControl;

  /**
   * @var \Drupal\auth_apic\Service\Interfaces\TokenParserInterface
   */
  protected TokenParserInterface $jwtParser;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  protected $chosen_registry;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  protected $inputForm;

  /**
   * ApicUserLoginForm constructor.
   *
   * @param \Drupal\user\UserFloodControlInterface $user_flood_control
   * @param \Drupal\user\UserStorageInterface $user_storage
   * @param \Drupal\user\UserAuthInterface $user_auth
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\Core\Render\BareHtmlPageRendererInterface $bare_html_renderer
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\ibm_apim\UserManagement\ApicAccountInterface $account_service
   * @param \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface $user_registry_service
   * @param \Drupal\ibm_apim\Service\ApimUtils $apim_utils
   * @param \Drupal\ibm_apim\Service\UserUtils $user_utils
   * @param \Drupal\ibm_apim\Service\SiteConfig $site_config
   * @param \Drupal\auth_apic\Service\Interfaces\OidcRegistryServiceInterface $oidc_service
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $session_store_factory
   * @param \Drupal\Core\Config\Config $ibm_settings_config
   * @param \Drupal\auth_apic\UserManagement\ApicLoginServiceInterface $login_service
   * @param \Drupal\auth_apic\UserManagement\ApicInvitationInterface $invitation_service
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct($user_flood_control,
                              UserStorageInterface $user_storage,
                              UserAuthInterface $user_auth,
                              RendererInterface $renderer,
                              BareHtmlPageRendererInterface $bare_html_renderer,
                              LoggerInterface $logger,
                              ApicAccountInterface $account_service,
                              UserRegistryServiceInterface $user_registry_service,
                              ApimUtils $apim_utils,
                              UserUtils $user_utils,
                              SiteConfig $site_config,
                              OidcRegistryServiceInterface $oidc_service,
                              PrivateTempStoreFactory $session_store_factory,
                              Config $ibm_settings_config,
                              ApicLoginServiceInterface $login_service,
                              ApicInvitationInterface $invitation_service,
                              Messenger $messenger,
                              ModuleHandlerInterface $module_handler,
                              TokenParserInterface $token_parser) {
    parent::__construct($user_flood_control, $user_storage, $user_auth, $renderer, $bare_html_renderer);
    $this->userFloodControl = $user_flood_control;
    $this->logger = $logger;
    $this->accountService = $account_service;
    $this->userRegistryService = $user_registry_service;
    $this->apimUtils = $apim_utils;
    $this->userUtils = $user_utils;
    $this->siteConfig = $site_config;
    $this->oidcService = $oidc_service;
    $this->authApicSessionStore = $session_store_factory->get('auth_apic_storage');
    $this->ibmSettingsConfig = $ibm_settings_config;
    $this->loginService = $login_service;
    $this->invitationService = $invitation_service;
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
    $this->jwtParser = $token_parser;
    $this->inputForm = InputForm::EmailInputForm;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\auth_apic\Form\ApicUserLoginForm|\Drupal\user\Form\UserLoginForm|static
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('user.flood_control'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('user.auth'),
      $container->get('renderer'),
      $container->get('bare_html_page_renderer'),
      $container->get('logger.channel.auth_apic'),
      $container->get('ibm_apim.account'),
      $container->get('ibm_apim.user_registry'),
      $container->get('ibm_apim.apim_utils'),
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.site_config'),
      $container->get('auth_apic.oidc'),
      $container->get('tempstore.private'),
      $container->get('config.factory')->get('ibm_apim.settings'),
      $container->get('auth_apic.login'),
      $container->get('auth_apic.invitation'),
      $container->get('messenger'),
      $container->get('module_handler'),
      $container->get('auth_apic.jwtparser')
    );
  }

  /**
   * @inheritDoc
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $baseForm = parent::buildForm($form, $form_state);

    $this->authApicSessionStore->set('action', 'signin');

    $is_owner_invitation = FALSE;

    // if we are on the invited user flow, there will be a JWT in the session so grab that
    $jwt = $this->authApicSessionStore->get('invitation_object');
    if ($jwt === NULL) {
      $inviteToken = \Drupal::request()->query->get('token');
      if ($inviteToken !== NULL) {
        $jwt = $this->jwtParser->parse($inviteToken);
        $this->authApicSessionStore->set('invitation_object', $jwt);
      }
    }
    if ($jwt !== NULL) {
      $form['#message']['message'] = t('To complete your invitation, sign in to an existing account or sign up to create a new account.');

      if (!strpos($jwt->getUrl(), '/member-invitations/')) {
        $is_owner_invitation = TRUE;
        // and for this case we need a consumer org title as well
        $baseForm['consumer_org'] = [
          '#type' => 'textfield',
          '#title' => t('Consumer organization'),
          '#description' => t('You are signing in with an existing account but have been invited to create a new consumer organization, please provide a name for that organization.'),
          '#size' => 60,
          '#maxlength' => 128,
          '#required' => TRUE,
        ];
      }
    }
    $this->authApicSessionStore->delete('redirect_to');
    if (\Drupal::request()->query->get('destination') === 'user/logout') {
      \Drupal::request()->query->remove('destination');
    }
    elseif (\Drupal::request()->query->get('redirectto') === 'user/logout') {
      \Drupal::request()->query->remove('redirectto');
    }
    if (\Drupal::request()->query->has('destination')) {
      $this->authApicSessionStore->set('redirect_to', \Drupal::request()->query->get('destination'));
    }
    elseif (\Drupal::request()->query->has('redirectto')) {
      $this->authApicSessionStore->set('redirect_to', \Drupal::request()->query->get('redirectto'));
    }

    // if the page was loaded due to invoking the subscription wizard, put up a more helpful piece of text on the form
    $subscription_wizard_cookie = \Drupal::request()->cookies->get('Drupal_visitor_startSubscriptionWizard');
    if (!empty($subscription_wizard_cookie)) {
      $form['#message']['message'] = t('Sign in to an existing account or create a new account to subscribe to this Product.');
    }

    // work out what user registries are enabled on this catalog
    $registries = $this->userRegistryService->getAll();

    $this->chosen_registry = $this->userRegistryService->getDefaultRegistry();
    $chosen_registry_url = \Drupal::request()->query->get('registry_url');

    // if there are no registries on the catalog throw up the default login page
    if (empty($registries)) {
      return $baseForm;
    }

    if (!empty($chosen_registry_url) && array_key_exists($chosen_registry_url, $registries) && ($chosen_registry_url === $this->userRegistryService->getAdminRegistryUrl() || $this->apimUtils->sanitizeRegistryUrl($chosen_registry_url) === 1)) {
      $this->chosen_registry = $registries[$chosen_registry_url];
    }
    if ($this->chosen_registry !== NULL) {
      $chosenRegistryURL = $this->chosen_registry->getUrl();
      $chosenRegistryTitle = $this->chosen_registry->getTitle();
    } else {
      // if no UR then fallback on using the admin UR (only an issue if we didnt get a UR from APIM)
      $this->chosen_registry = $registries[$this->userRegistryService->getAdminRegistryUrl()];
      if ($this->chosen_registry !== NULL) {
        $chosenRegistryURL = $this->chosen_registry->getUrl();
        $chosenRegistryTitle = $this->chosen_registry->getTitle();
      } else {
        $chosenRegistryURL = 'error';
        $chosenRegistryTitle = 'ERROR';
      }
    }

    // store registry_url for validate/submit
    $form['registry_url'] = [
      '#type' => 'hidden',
      '#value' => $chosenRegistryURL,
    ];

    // store registry_url for template
    $form['#registry_url']['registry_url'] = $chosenRegistryURL;

    // build the form
    // Build a container for the section headers to go in
    $form['headers_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['apic-user-form-container']],
    ];

    // Explain this part of the form
    $form['headers_container']['signin_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => t('Sign in with @registryName', ['@registryName' => $chosenRegistryTitle]),
      '#attributes' => ['class' => ['apic-user-form-subheader']],
      '#weight' => -1000,
    ];

    // Build the form by embedding the other forms
    // Wrap everything in a container so we can set flex display
    $form['main_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['apic-user-form-container', 'apic-user-form-container-secure']],
    ];

    // Embed the default log in form
    // Wrap the whole form in a div that we can style.
    $baseForm['#prefix'] = '<div class="apic-user-form-inner-wrapper">';
    $baseForm['#suffix'] = '</div>';

    // Make username and password not required as this prevents form submission if clicking one of the
    // buttons on the right hand side
    $baseForm['pass']['#required'] = FALSE;
    $baseForm['pass']['#attributes'] = ['autocomplete' => 'off'];
    $baseForm['email']['#required'] = FALSE;
    $baseForm['email']['#attributes'] = ['autocomplete' => 'off'];
    if ($this->inputForm == InputForm::EmailInputForm) {
      //Hide default credentials    
      $baseForm['name'] = [];
      $baseForm['pass'] = [];

      $baseForm['email'] = [
        '#type' => 'email',
        '#title' => $this->t('Email address'),
        '#default_value' => $form_state->get('cached_email') ?? '',
      ];
    }
    else if ($this->inputForm == InputForm::CredentialsInputForm) {
      //Hide render of name and email input forms
      $baseForm['name'] = [];
      $baseForm['email'] = [];
      $baseForm['actions']['back_button'] = [
        '#type' => 'submit',
        '#name' => 'back_button',
        '#value' => $this->t('Back'),
        '#submit' => ['::backToMailForm'],
        '#attributes' => [
          'class' => ['btn', 'btn-primary'],
        ],
        '#validate' => [],
        '#limit_validation_errors' => [],
        '#weight' => 1,
      ];
    }

    $form['main_container']['plainlogin'] = $baseForm;

    $form['#attached']['library'][] = 'ibm_apim/single_click';
    $form['#attached']['library'][] = 'ibm_apic_secure_auth/ibm_apic_secure_auth';
    if ($this->moduleHandler->moduleExists('page_load_progress') && \Drupal::currentUser()->hasPermission('use page load progress')) {

      // Unconditionally attach assets to the page.
      $form['#attached']['library'][] = 'auth_apic/oidc_page_load_progress';

      $pjp_config = \Drupal::config('page_load_progress.settings');
      // Attach config settings.
      $form['#attached']['drupalSettings']['oidc_page_load_progress'] = [
        'esc_key' => $pjp_config->get('page_load_progress_esc_key'),
      ];
    }
    if ($this->moduleHandler->moduleExists('social_media_links')) {
      $form['#attached']['library'][] = 'social_media_links/fontawesome.component';
    }

    // need to add cache context for the query param
    if (!isset($form['#cache'])) {
      $form['#cache'] = [];
    }
    if (!isset($form['#cache']['contexts'])) {
      $form['#cache']['contexts'] = [];
    }
    $form['#cache']['contexts'][] = 'url.query_args:registry_url';

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $trigger = $form_state->getTriggeringElement();
    $button_name = $trigger['#name'] ?? '';
    if ($button_name === 'back_button') {
      return;
    }

    if ($this->inputForm == InputForm::EmailInputForm) {
      $apicAuthenticated = $this->validateApicAuthenticationEmail($form, $form_state);
      if ($apicAuthenticated !== TRUE) {
        $form_state->setErrorByName('useremail', $this->t('Unable to sign in. This may be because the credentials provided for authentication are invalid or the user has not been activated. Please check that the user is active, then repeat the request with valid credentials. Please note that repeated attempts with incorrect credentials can lock the user account.'));
      }
      if (!empty($form_state->getErrors())) {
        $this->messenger->addError(t('Unauthorized'));
      }
    } else if($this->inputForm == InputForm::CredentialsInputForm) {
      $name = $this->getUsername($form_state->get('cached_email'));
      if ($this->chosen_registry !== NULL && $this->chosen_registry->getRegistryType() !== 'oidc') {
        $this->validateName($form, $form_state);
        if (empty($form_state->getErrors())) {
          $apicAuthenticated = $this->validateApicAuthentication($form, $form_state);
          if ($apicAuthenticated !== TRUE) {
            $user_input = $form_state->getUserInput();
            $query = isset($name) ? ['name' => $name] : [];
            $form_state->setErrorByName('usernameorpassword', $this->t('Unable to sign in. This may be because the credentials provided for authentication are invalid or the user has not been activated. Please check that the user is active, then repeat the request with valid credentials. Please note that repeated attempts with incorrect credentials can lock the user account.'));
            $form_state->setErrorByName('usernameorpassword2', $this->t('<a href=":password">Forgot your password? Click here to reset it.</a>', [
              ':password' => Url::fromRoute('user.pass', [], ['query' => $query])
                ->toString(),
            ]));
          }
        }
  
        $this->validateFinal($form, $form_state);
        if (!empty($form_state->getErrors())) {
          $this->messenger->addError(t('Unauthorized'));
        }
      }
    }    
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return bool
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function validateApicAuthentication(array &$form, FormStateInterface $form_state): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    global $base_url;
    $returnValue = FALSE;
    if ($this->validateFloodProtection($form, $form_state)) {
      $name = $this->getUsername($form_state->get('cached_email'));
      $password = $form_state->getValue('pass');
      $corg = $form_state->getValue('consumer_org');

      // maybe this was an invited user?
      $jwt = $this->authApicSessionStore->get('invitation_object');

      $admin = $this->userStorage->load(1);
      // special case the admin user and log in via standard drupal mechanism.
      if ($admin !== NULL && $name === $admin->getAccountName()) {
        if ($jwt !== NULL) {
          $this->messenger->addError(t('admin user is not allowed when signing in an invited user.'));
          $returnValue = FALSE;
        }
        else {
          $this->logger->debug('admin login, using core validation for login');
          $floodProtection =  $this->validateFloodProtection($form, $form_state);
          if ($floodProtection === TRUE) {
            $form_state->set('uid', $admin->id());
            $returnValue = TRUE;
          }
          else {
            \Drupal::service('ibm_apim.utils')->logAuditEvent('PORTAL_AUTHENTICATE', 'failure', 'service/security/account/user', $base_url . '/user/' . $admin->id());
            $this->messenger->addError(t('Unauthorized'));
            $form_state->set('uid', -1);
            $returnValue = FALSE;
          }
        }
      }
      else {
        $login_user = new ApicUser();
        $login_user->setUsername($name);
        $login_user->setPassword($password);
        if (!empty($corg)) {
          $login_user->setOrganization($corg);
        }
        $login_user->setApicUserRegistryURL($form_state->getValue('registry_url'));

        $registry = $this->userRegistryService->get($form_state->getValue('registry_url'));
        if ($registry !== NULL) {

          if ($jwt !== NULL) {
            $response = $this->invitationService->acceptInvite($jwt, $login_user);

            if (isset($response) && $response->success() === TRUE) {
              if ($response->getMessage()) {
                $this->messenger->addStatus($response->getMessage());
              }
              $response = $this->loginService->login($login_user);
            }
          }
          else {
            $response = $this->loginService->login($login_user);
          }
          if (isset($response) && $response->success()) {
            $this->authApicSessionStore->delete('invitation_object');
            if ($response->getMessage() === 'APPROVAL') {
              $form_state->set('approval', TRUE);
              $form_state->set('uid', -1);
            } else {
              $form_state->set('uid', $response->getUid());
            }
            $returnValue = TRUE;
          }
          else {
            // unsuccessful login.
            $returnValue = FALSE;
          }
        }
        else {
          $this->logger->error('Failed to login. Unable to determine registry to use from login form.');
          $returnValue = FALSE;
        }
      }
    }
    if (!$returnValue) {
      $this->logger->error('Login attempt for %user which failed in validateApicAuthentication.', ['%user' => $this->getUsername($form_state->get('cached_email'))]);
      $this->messenger->addError(t('Unauthorized'));
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  public function validateApicAuthenticationEmail(array &$form, FormStateInterface $form_state): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $returnValue = TRUE;
    //Validate if user enter email credentials
    $email = $form_state->getValue('email');
    if ($email == '' || $email === NULL) {
      $returnValue = FALSE;
    }

    //Check if user with coresponding email exist
    $account = $this->userStorage->loadByProperties(['mail' => $email, 'status' => 1]);
    if (!$account) {
      $returnValue = FALSE;
    }

    //Check if user registry exist
    $registry = $this->getUserRegistry($email);
    if (!$registry) {
      $returnValue = FALSE;
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Taken from UserLoginForm::validateAuthentication().
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return bool
   */
  protected function validateFloodProtection(array $form, FormStateInterface $form_state): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $returnValue = TRUE;
    return TRUE;
    $password = trim($form_state->getValue('pass'));
    $flood_config = $this->config('user.flood');
    $userName = $this->getUsername($form_state->get('cached_email'));
    if ($password !== '' && $userName !== NULL) {
      // Do not allow any login from the current user's IP if the limit has been
      // reached. Default is 50 failed attempts allowed in one hour. This is
      // independent of the per-user limit to catch attempts from one IP to log
      // in to many different user accounts.  We have a reasonably high limit
      // since there may be only one apparent IP for all users at an institution.
      if (!$this->userFloodControl->isAllowed('user.failed_login_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
        $form_state->set('flood_control_triggered', 'ip');
        $returnValue = FALSE;
      }
      $accounts = $this->userStorage->loadByProperties(['name' => $userName, 'status' => 1]);
      $account = reset($accounts);
      if ($account) {
        if ($flood_config->get('uid_only')) {
          // Register flood events based on the uid only, so they apply for any
          // IP address. This is the most secure option.
          $identifier = $account->id();
        }
        else {
          // The default identifier is a combination of uid and IP address. This
          // is less secure but more resistant to denial-of-service attacks that
          // could lock out all users with public user names.
          $identifier = $account->id() . '-' . $this->getRequest()->getClientIp();
        }
        $form_state->set('flood_control_user_identifier', $identifier);

        // Don't allow login if the limit for this user has been reached.
        // Default is to allow 5 failed attempts every 6 hours.
        if (!$this->userFloodControl->isAllowed('user.failed_login_user', $flood_config->get('user_limit'), $flood_config->get('user_window'), $identifier)) {
          $form_state->set('flood_control_triggered', 'user');
          $returnValue = FALSE;
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  public function validateFinal(array &$form, FormStateInterface $form_state) {
    $userName = $this->getUsername($form_state->get('cached_email'));
    $flood_config = $this->config('user.flood');
    if (!$form_state->get('uid')) {
      // Always register an IP-based failed login event.
      $this->userFloodControl->register('user.failed_login_ip', $flood_config->get('ip_window'));
      // Register a per-user failed login event.
      if ($flood_control_user_identifier = $form_state->get('flood_control_user_identifier')) {
        $this->userFloodControl->register('user.failed_login_user', $flood_config->get('user_window'), $flood_control_user_identifier);
      }

      if ($flood_control_triggered = $form_state->get('flood_control_triggered')) {
        if ($flood_control_triggered == 'user') {
          $message = $this->formatPlural($flood_config->get('user_limit'), 'There has been more than one failed login attempt for this account. It is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', 'There have been more than @count failed login attempts for this account. It is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', [':url' => Url::fromRoute('user.pass')->toString()]);
        }
        else {
          // We did not find a uid, so the limit is IP-based.
          $message = $this->t('Too many failed login attempts from your IP address. This IP address is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', [':url' => Url::fromRoute('user.pass')->toString()]);
        }
        $response = $this->bareHtmlPageRenderer->renderBarePage(['#markup' => $message], $this->t('Login failed'), 'maintenance_page__flood');
        $response->setStatusCode(403);
        $form_state->setResponse($response);
      }
      else {
        $form_state->setErrorByName('password', $this->t('Unrecognized password. <a href=":password">Forgot your password?</a>', [':password' => Url::fromRoute('user.pass')->toString()]));
        $accounts = $this->userStorage->loadByProperties(['name' => $userName]);
        if (!empty($accounts)) {
          $this->logger('user')->notice('Login attempt failed for %user.', ['%user' => $userName]);
        }
        else {
          // If the username entered is not a valid user,
          // only store the IP address.
          $this->logger('user')->notice('Login attempt failed from %ip.', ['%ip' => $this->getRequest()->getClientIp()]);
        }
      }
    }
    elseif (!$form_state->get('flood_control_skip_clear') && $flood_control_user_identifier = $form_state->get('flood_control_user_identifier')) {
      // Clear past failures for this user so as not to block a user who might
      // log in and out more than once in an hour.
      $this->userFloodControl->clear('user.failed_login_user', $flood_control_user_identifier);
    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if ($this->inputForm == InputForm::EmailInputForm) {
      $this->chosen_registry = $this->getUserRegistry($form_state->getValue('email'));
      //rebuild form if related registry type attached to user email, do not belong to oidc, and requires usernickaname and pswd
      if ($this->chosen_registry->getRegistryType() != 'oidc') {
        $form_state->set('cached_email', $form_state->getValue('email'));
        $this->inputForm = InputForm::CredentialsInputForm;
        $form_state->setRebuild(TRUE);
        return;
      }
    }
    
    if ($this->chosen_registry->getRegistryType() === 'oidc') {
      $jwt = $this->authApicSessionStore->get('invitation_object');
      $oidc_url = $form_state->getValue('oidc_url');
      if (!empty($jwt) && !strpos($jwt->getUrl(), '/member-invitations/') && $oidc_url) {
        $oidc_url .= "&invitation_scope=consumer-org&title=" . urlencode($form_state->getValue('consumer_org'));
      } else {
        $oidc_info = $this->oidcService->getOidcMetadata($this->chosen_registry);
        $oidc_url = $oidc_info['az_url'] . '&action=signin';
      }
      $response = new TrustedRedirectResponse(Url::fromUri($oidc_url)->toString());
      $form_state->setResponse($response);
    }
    else {
      if ($form_state->get('approval') === TRUE) {
        $this->messenger->addStatus($this->t('Your account was created successfully and is pending approval. You will receive an email with further instructions.'));
        return;
      }
      // parent form will actually log the use in...
      parent::submitForm($form, $form_state);
      // now we need to check whether:
      // - this is a first time login?
      // - user needs to pick up in a subscription wizard?
      // - user isn't in a consumer org?

      $current_user = \Drupal::currentUser();
      $first_time_login = NULL;
      $subscription_wizard_cookie = NULL;

      if (isset($current_user)) {
        $current_user = User::load($current_user->id());
        $first_time_login = $current_user->first_time_login->value;
        $subscription_wizard_cookie = \Drupal::request()->cookies->get('Drupal_visitor_startSubscriptionWizard');
      }

      // check if the user we just logged in is a member of at least one dev org
      $current_corg = $this->userUtils->getCurrentConsumerorg();
      if (!isset($current_corg)) {
        // if onboarding is enabled, we can redirect to the create org page
        if ($this->siteConfig->isSelfOnboardingEnabled()) {
          $form_state->setRedirect('consumerorg.create');
        }
        else {
          // we can't help the user, they need to talk to an administrator
          $form_state->setRedirect('ibm_apim.noperms');
        }
        // if no consumer org then return early, everything else is secondary.
        return;
      }
      if ($this->authApicSessionStore->get('redirect_to')) {
        $this->authApicSessionStore->delete('redirect_to');
      }
      if (isset($current_user) && (int) $first_time_login !== 0 && empty($subscription_wizard_cookie)) {
        // set first_time_login to 0 for next time
        $current_user->set('first_time_login', 0);
        $current_user->save();

        $form_state->setRedirect('ibm_apim.get_started');
      }
      elseif (!empty($subscription_wizard_cookie)) {
        // If the startSubscriptionWizard cookie is set, grab the value from it, set up a redirect and delete it
        $form_state->setRedirect('ibm_apim.subscription_wizard.step', [
          'step' => 'chooseplan',
          'productId' => $subscription_wizard_cookie,
        ]);
        user_cookie_delete('startSubscriptionWizard');
      }
      else {
        // this is for the 404 redirect from the apic_app module
        $destination = \Drupal::request()->get('redirectto');
        if (isset($destination) && !empty($destination)) {
          if ($destination[0] !== '/' && $destination[0] !== '?' && $destination[0] !== '#') {
            $destination = '/' . $destination;
          }
          $form_state->setRedirectUrl(Url::fromUserInput($destination));
        }
        else {
          $form_state->setRedirect('<front>');
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  private function getUser($email) {
    $account = $this->userStorage->loadByProperties(['mail' => $email, 'status' => 1]);
    if (!$account) {
      return NULL;
    }
    return reset($account);
  }

  /**
   * @param $email
   * @return UserRegistry
   */
  private function getUserRegistry($email) {
    $account = $this->getUser($email);
    if (!$account) {
      return NULL;
    }
    $accound_user_registry = $account->get('registry_url')->value;
    if ($accound_user_registry === '/admin') {
      return $this->getAdminRegistry();
    }
    return $this->userRegistryService->get($accound_user_registry);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function backToMailForm(array &$form, FormStateInterface $form_state) {
    $this->chosen_registry = NULL;
    $this->inputForm = InputForm::EmailInputForm;
    $form_state->setRebuild(TRUE);
  }

  private function getUsername($email): string {
    $account = $this->getUser($email);
    if (!$account) {
      return NULL;
    }
    return $account->get('name')->value;
  }

    /**
   * @param $registries
   */
  private function getAdminRegistry(): UserRegistry {
    $admin_reg = new UserRegistry();
    $admin_reg->setRegistryType('admin_only');
    $admin_reg->setUserManaged(TRUE);
    $admin_reg->setName('admin_only');
    $admin_reg->setTitle('admin');
    $admin_reg->setUrl($this->userRegistryService->getAdminRegistryUrl());
    return $admin_reg;
  }
}
