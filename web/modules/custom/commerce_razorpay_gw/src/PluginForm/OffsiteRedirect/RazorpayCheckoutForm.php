<?php

namespace Drupal\commerce_razorpay_gw\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Razorpay\Api\Api;

class RazorpayCheckoutForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order   = $payment->getOrder();

    /** @var \Drupal\commerce_razorpay_gw\Plugin\Commerce\PaymentGateway\RazorpayCheckout $plugin */
    $plugin = $payment->getPaymentGateway()->getPlugin();
    $config = $plugin->getConfiguration();

    $price    = $order->getTotalPrice();
    $amount   = (int) round((float) $price->getNumber() * 100);
    // Use gateway-configured currency; free Razorpay test accounts require INR.
    $currency = $config['currency_code'] ?: $price->getCurrencyCode();

    $return_url = $form['#return_url'];
    $cancel_url = $form['#cancel_url'];

    // Create a Razorpay order so that we get an order_id for the JS modal.
    try {
      $api = new Api($config['key_id'], $config['key_secret']);
      $razorpay_order = $api->order->create([
        'receipt'  => 'drupal_' . $order->id(),
        'amount'   => $amount,
        'currency' => $currency,
      ]);
    }
    catch (\Exception $e) {
      throw new PaymentGatewayException('Could not create Razorpay order: ' . $e->getMessage());
    }

    // Build a POST-redirect form whose action points to Commerce's own return
    // URL. Razorpay JS will fill in the three hidden fields and submit.
    $data = [
      'razorpay_payment_id' => '',
      'razorpay_order_id'   => $razorpay_order->id,
      'razorpay_signature'  => '',
    ];
    $form = $this->buildRedirectForm($form, $form_state, $return_url, $data, self::REDIRECT_POST);

    // Remove Commerce's auto-submit JS — we open Razorpay instead.
    unset($form['#attached']['library']);
    unset($form['commerce_message']);

    // Attach Razorpay checkout.js and our initialisation script.
    $form['#attached']['library'][]                      = 'commerce_razorpay_gw/razorpay_checkout';
    $form['#attached']['drupalSettings']['razorpay'] = [
      'key'         => $config['key_id'],
      'amount'      => $amount,
      'currency'    => $currency,
      'orderId'     => $razorpay_order->id,
      'name'        => \Drupal::config('system.site')->get('name'),
      'description' => 'Order #' . $order->id(),
      'cancelUrl'   => $cancel_url,
    ];

    return $form;
  }

}
