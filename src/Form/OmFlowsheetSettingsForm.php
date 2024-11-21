<?php

/**
 * @file
 * Contains \Drupal\om_flowsheet\Form\OmFlowsheetSettingsForm.
 */

namespace Drupal\om_flowsheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class OmFlowsheetSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'om_flowsheet_settings_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['emails'] = [
      '#type' => 'textfield',
      '#title' => t('(Bcc) Notification emails'),
      '#description' => t('Specify emails id for Bcc option of mail system with comma separated'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_emails'),
    ];
    $form['cc_emails'] = [
      '#type' => 'textfield',
      '#title' => t('(Cc) Notification emails'),
      '#description' => t('Specify emails id for Cc option of mail system with comma separated'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_cc_emails'),
    ];
    $form['from_email'] = [
      '#type' => 'textfield',
      '#title' => t('Outgoing from email address'),
      '#description' => t('Email address to be display in the from field of all outgoing messages'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_from_email'),
    ];
    $form['extensions']['abstract_upload'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed abstract file extensions'),
      '#description' => t('A comma separated list WITHOUT SPACE of pdf file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_abstract_upload_extensions'),
    ];
    $form['extensions']['flowsheet_upload'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed om flowsheet for the developed process'),
      '#description' => t('A comma separated list WITHOUT SPACE of pdf file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_flowsheet_developed_process_source_extensions'),
    ];
    $form['extensions']['simulator_package_upload'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed om flowsheet for the simulator package'),
      '#description' => t('A comma separated list WITHOUT SPACE of pdf file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_simulator_package_extensions'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    return;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('om_flowsheet.settings')->set('om_flowsheet_emails', $form_state->getValue(['emails']))->save();
    \Drupal::configFactory()->getEditable('om_flowsheet.settings')->set('om_flowsheet_cc_emails', $form_state->getValue(['cc_emails']))->save();
    \Drupal::configFactory()->getEditable('om_flowsheet.settings')->set('om_flowsheet_from_email', $form_state->getValue(['from_email']))->save();
    \Drupal::configFactory()->getEditable('om_flowsheet.settings')->set('om_flowsheet_abstract_upload_extensions', $form_state->getValue(['abstract_upload']))->save();
    \Drupal::configFactory()->getEditable('om_flowsheet.settings')->set('om_flowsheet_flowsheet_developed_process_source_extensions', $form_state->getValue(['flowsheet_upload']))->save();
    \Drupal::configFactory()->getEditable('om_flowsheet.settings')->set('om_flowsheet_simulator_package_extensions', $form_state->getValue(['simulator_package_upload']))->save();
    \Drupal::messenger()->addStatus(t('Settings updated'));
  }

}
?>
