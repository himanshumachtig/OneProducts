<?php

namespace Drupal\commerce_razorpay_gw\Plugin\Commerce\PaymentGateway;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Attribute\CommercePaymentGateway;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_razorpay_gw\PluginForm\OffsiteRedirect\RazorpayCheckoutForm;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Symfony\Component\HttpFoundation\Request;

#[CommercePaymentGateway(
  id: 'razorpay_checkout',
  label: new TranslatableMarkup('Razorpay'),
  display_label: new TranslatableMarkup('Razorpay'),
  forms: [
    'offsite-payment' => RazorpayCheckoutForm::class,
  ],
  payment_method_types: ['credit_card'],
  credit_card_types: ['amex', 'dinersclub', 'discover', 'maestro', 'mastercard', 'visa'],
  requires_billing_information: FALSE,
)]
class RazorpayCheckout extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'key_id'        => '',
      'key_secret'    => '',
      'currency_code' => 'INR',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['key_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Key ID'),
      '#default_value' => $this->configuration['key_id'],
      '#required'      => TRUE,
      '#description'   => $this->t('Your Razorpay Key ID (e.g. rzp_test_...).'),
    ];

    $form['key_secret'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Key Secret'),
      '#default_value' => $this->configuration['key_secret'],
      '#required'      => TRUE,
    ];

    $form['currency_code'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Currency Code'),
      '#default_value' => $this->configuration['currency_code'],
      '#required'      => TRUE,
      '#description'   => $this->t('Currency sent to Razorpay (e.g. INR, USD). Free test accounts require INR.'),
      '#size'          => 6,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    $this->configuration['key_id']        = $values['key_id'];
    $this->configuration['key_secret']    = $values['key_secret'];
    $this->configuration['currency_code'] = strtoupper(trim($values['currency_code']));
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request): void {
    $payment_id = $request->request->get('razorpay_payment_id', '');
    $order_id   = $request->request->get('razorpay_order_id', '');
    $signature  = $request->request->get('razorpay_signature', '');

    if (!$payment_id || !$order_id || !$signature) {
      throw new PaymentGatewayException('Missing Razorpay payment parameters.');
    }

    try {
      $api = new Api($this->configuration['key_id'], $this->configuration['key_secret']);
      $api->utility->verifyPaymentSignature([
        'razorpay_order_id'   => $order_id,
        'razorpay_payment_id' => $payment_id,
        'razorpay_signature'  => $signature,
      ]);
    }
    catch (SignatureVerificationError $e) {
      throw new PaymentGatewayException('Razorpay payment signature verification failed.');
    }

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state'           => 'completed',
      'amount'          => $order->getBalance(),
      'payment_gateway' => $this->parentEntity->id(),
      'order_id'        => $order->id(),
      'remote_id'       => $payment_id,
      'remote_state'    => 'captured',
    ]);
    $payment->save();
  }

}
