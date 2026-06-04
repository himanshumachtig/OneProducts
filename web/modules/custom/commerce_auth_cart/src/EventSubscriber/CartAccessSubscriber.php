<?php

namespace Drupal\commerce_auth_cart\EventSubscriber;

use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects anonymous users to login for all cart and checkout routes.
 *
 * Why: Drupal Commerce allows anonymous carts by default. These accumulate as
 * uid=0 orders and create two problems:
 *   1. Cart merging on login can expose unexpected items to a newly logged-in user.
 *   2. The 'cart' cache context produces the same key ("") for all users with
 *      empty carts, so dynamic-page-cached pages leak usernames across sessions.
 * Blocking anonymous access here eliminates both problems at the entry point.
 */
class CartAccessSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected AccountInterface $currentUser,
    protected RedirectDestinationInterface $redirectDestination,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Priority 30 runs before most request subscribers but after routing.
    return [KernelEvents::REQUEST => ['onRequest', 30]];
  }

  /**
   * Redirects anonymous users to /user/login for protected cart routes.
   */
  public function onRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }
    if ($this->currentUser->isAuthenticated()) {
      return;
    }

    $request = $event->getRequest();
    $route_name = $request->attributes->get('_route');

    if (!$this->isProtectedRoute($route_name)) {
      return;
    }

    // Save the intended destination so the user is sent back after login.
    $destination = $this->redirectDestination->getAsArray();
    $login_url = Url::fromRoute('user.login', [], ['query' => $destination])
      ->toString();

    $event->setResponse(new RedirectResponse($login_url, 302));
  }

  /**
   * Returns TRUE for any route that requires an authenticated cart session.
   */
  protected function isProtectedRoute(?string $route_name): bool {
    if (!$route_name) {
      return FALSE;
    }

    $protected = [
      'commerce_cart.page',
      'commerce_checkout.form',
      'commerce_checkout.checkout',
    ];

    if (in_array($route_name, $protected, TRUE)) {
      return TRUE;
    }

    // Protect all commerce_checkout.* sub-routes.
    if (str_starts_with($route_name, 'commerce_checkout.')) {
      return TRUE;
    }

    return FALSE;
  }

}
