<?php

namespace Drupal\om_flowsheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

class VerifyCertificatesForm extends FormBase {

  public function getFormId() {
    return 'verify_certificates_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['qr_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter QR Code'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Verify'),
      '#ajax' => [
        'callback' => '::submitAjax',
        'event' => 'click',
      ],
    ];

    $form['results'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'verification-results'],
    ];

    return $form;
  }

  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $qr_code = $form_state->getValue('qr_code');
    //var_dump($qr_code);die;
    $controller = \Drupal::service('controller_resolver')->getControllerFromDefinition('\Drupal\om_flowsheet\Controller\VerifyCertificatesController::verifyQRCodeFromDB');
    $result = $controller($qr_code);
    //var_dump($result);die;
    $response->addCommand(new HtmlCommand('#verification-results', $result));
    return $response;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This method is required but not used for AJAX submissions.
  }
}
