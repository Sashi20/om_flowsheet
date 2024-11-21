<?php

/**
 * @file
 * Contains \Drupal\om_flowsheet\Form\OmFlowsheetAbstractBulkApprovalForm.
 */

namespace Drupal\om_flowsheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class OmFlowsheetAbstractBulkApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'om_flowsheet_abstract_bulk_approval_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $options_first = _bulk_list_of_flowsheet_project();
    $selected = !$form_state->getValue(['flowsheet_project']) ? $form_state->getValue([
      'flowsheet_project'
      ]) : key($options_first);
    $form = [];
    $form['flowsheet_project'] = [
      '#type' => 'select',
      '#title' => t('Title of the flowsheeting project'),
      '#options' => _bulk_list_of_flowsheet_project(),
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => 'ajax_bulk_flowsheet_abstract_details_callback'
        ],
      '#suffix' => '<div id="ajax_selected_flowsheet"></div><div id="ajax_selected_flowsheet_pdf"></div>',
    ];
    $form['flowsheet_actions'] = [
      '#type' => 'select',
      '#title' => t('Please select action for Flowsheeting project'),
      '#options' => _bulk_list_flowsheet_actions(),
      '#default_value' => 0,
      '#prefix' => '<div id="ajax_selected_flowsheet_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="flowsheet_project"]' => [
            'value' => 0
            ]
          ]
        ],
    ];
    $form['disapprove_message'] = [
      '#type' => 'textarea',
      '#title' => t('Enter the reason for disapproving the proposal<span style="color: red;">*</span>'),
      '#prefix' => '<div id= "message_submit">',
      '#states' => [
        'visible' => [
          [
            ':input[name="flowsheet_actions"]' => [
              'value' => 2
              ]
            ]
          ]
        ],
    ];
    $form['deletion_message'] = [
      '#type' => 'textarea',
      '#title' => t('Enter the reason for deleting the proposal'),
      '#prefix' => '<div id= "message_submit">',
      '#states' => [
        'visible' => [
          [
            ':input[name="flowsheet_actions"]' => [
              'value' => 3
              ]
            ]
          ]
        ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#states' => [
        'invisible' => [
          ':input[name="lab"]' => [
            'value' => 0
            ]
          ]
        ],
    ];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $msg = '';
    $root_path = om_flowsheet_document_path();
    if ($form_state->get(['clicked_button', '#value']) == 'Submit') {
      if ($form_state->getValue(['flowsheet_project']))
        // om_flowsheet_abstract_del_lab_pdf($form_state['values']['flowsheet_project']);
 {
        if (\Drupal::currentUser()->hasPermission('om flowsheet bulk manage abstract')) {
          $query = \Drupal::database()->select('om_flowsheet_proposal');
          $query->fields('om_flowsheet_proposal');
          $query->condition('id', $form_state->getValue(['flowsheet_project']));
          $user_query = $query->execute();
          $user_info = $user_query->fetchObject();
          $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($user_info->uid);
          if ($form_state->getValue(['flowsheet_actions']) == 1) {
            // approving entire project //
            $query = \Drupal::database()->select('om_flowsheet_submitted_abstracts');
            $query->fields('om_flowsheet_submitted_abstracts');
            $query->condition('proposal_id', $form_state->getValue(['flowsheet_project']));
            $abstracts_q = $query->execute();
            $experiment_list = '';
            while ($abstract_data = $abstracts_q->fetchObject()) {
              \Drupal::database()->query("UPDATE {om_flowsheet_submitted_abstracts} SET abstract_approval_status = 1, approver_uid = :approver_uid WHERE id = :id", [
                ':approver_uid' => $user->uid,
                ':id' => $abstract_data->id,
              ]);
              \Drupal::database()->query("UPDATE {om_flowsheet_submitted_abstracts_file} SET file_approval_status = 1, approvar_uid = :approver_uid WHERE submitted_abstract_id = :submitted_abstract_id", [
                ':approver_uid' => $user->uid,
                ':submitted_abstract_id' => $abstract_data->id,
              ]);
            } //$abstract_data = $abstracts_q->fetchObject()
            \Drupal::messenger()->addStatus(t('Approved Flowsheeting project.'));
            // email 
            // @FIXME
            // // @FIXME
            // // This looks like another module's variable. You'll need to rewrite this call
            // // to ensure that it uses the correct configuration object.
            // $email_subject = t('[!site_name][Flowsheeting Project] Your uploaded OpenModelica Flowsheeting Project has been approved', array(
            // 						'!site_name' => variable_get('site_name', '')
            // 					));

            // @FIXME
            // // @FIXME
            // // This looks like another module's variable. You'll need to rewrite this call
            // // to ensure that it uses the correct configuration object.
            // $email_body = array(
            // 						0 => t('
            // 
            // Dear ' . $user_info->contributor_name . ',
            // 
            // Congratulations!
            // Your OpenModelica flowsheet and abstract with the following details have been approved.
            // 
            // Full Name: ' . $user_info->name_title . ' ' . $user_info->contributor_name . '
            // Project Title: ' . $user_info->project_title . '
            // Name of compound for which process development is carried out: ' . $user_info->process_development_compound_name . '
            // 
            // Kindly send us the internship forms available at https://om.fossee.in/chemical/flowsheeting-project/internship/forms as early as possible for processing your honorarium on time. In case you have already sent these forms, please share the the consignment number or tracking id with us.
            // 
            // Note: It will take upto 45 days from the time we receive your forms, to process your honorarium.
            // 
            // Best Wishes,
            // 
            // !site_name Team
            // FOSSEE, IIT Bombay', array(
            // 							'!site_name' => variable_get('site_name', ''),
            // 							'!user_name' => $user_data->name
            // 						))
            // 					);

            /** sending email when everything done **/
            $email_to = $user_data->mail;
            $from = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_from_email');
            $bcc = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_emails');
            $cc = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_cc_emails');
            $params['standard']['subject'] = $email_subject;
            $params['standard']['body'] = $email_body;
            $params['standard']['headers'] = [
              'From' => $from,
              'MIME-Version' => '1.0',
              'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
              'Content-Transfer-Encoding' => '8Bit',
              'X-Mailer' => 'Drupal',
              'Cc' => $cc,
              'Bcc' => $bcc,
            ];
            if (!drupal_mail('om_flowsheet', 'standard', $email_to, language_default(), $params, $from, TRUE)) {
              $msg = \Drupal::messenger()->addError('Error sending email message.');
            } //!drupal_mail('om_flowsheet', 'standard', $email_to, language_default(), $params, $from, TRUE)
          } //$form_state['values']['flowsheet_actions'] == 1
          elseif ($form_state->getValue(['flowsheet_actions']) == 2) {
            //pending review entire project 
            if (strlen(trim($form_state->getValue(['disapprove_message']))) == 0) {
              $form_state->setErrorByName('disapprove_message', t(''));
              $msg = \Drupal::messenger()->addError("Please mention the reason for disapproval.");
              return $msg;
            }
            else {
              if (strlen(trim($form_state->getValue(['disapprove_message']))) <= 30) {
                $form_state->setErrorByName('disapprove_message', t(''));
                $msg = \Drupal::messenger()->addError("Please mention the reason for disapproval. Minimum 30 character required");
                return $msg;
              }
            }
            $query = \Drupal::database()->select('om_flowsheet_submitted_abstracts');
            $query->fields('om_flowsheet_submitted_abstracts');
            $query->condition('proposal_id', $form_state->getValue(['flowsheet_project']));
            $abstracts_q = $query->execute();
            $experiment_list = '';
            while ($abstract_data = $abstracts_q->fetchObject()) {
              \Drupal::database()->query("UPDATE {om_flowsheet_submitted_abstracts} SET abstract_approval_status = 0,  is_submitted = 0, approver_uid = :approver_uid WHERE id = :id", [
                ':approver_uid' => $user->uid,
                ':id' => $abstract_data->id,
              ]);
              \Drupal::database()->query("UPDATE {om_flowsheet_submitted_abstracts_file} SET file_approval_status = 0, approvar_uid = :approver_uid WHERE submitted_abstract_id = :submitted_abstract_id", [
                ':approver_uid' => $user->uid,
                ':submitted_abstract_id' => $abstract_data->id,
              ]);
            } //$abstract_data = $abstracts_q->fetchObject()
            \Drupal::messenger()->addStatus(t('Disapproved the submission.'));
            // email 
            // @FIXME
            // // @FIXME
            // // This looks like another module's variable. You'll need to rewrite this call
            // // to ensure that it uses the correct configuration object.
            // $email_subject = t('[!site_name][Flowsheeting Project] Your uploaded OpenModelica Flowsheeting Project has been marked as disapproved', array(
            // 						'!site_name' => variable_get('site_name', '')
            // 					));

            // @FIXME
            // // @FIXME
            // // This looks like another module's variable. You'll need to rewrite this call
            // // to ensure that it uses the correct configuration object.
            // $email_body = array(
            // 						0 => t('
            // 
            // Dear ' . $user_info->contributor_name . ',
            // 
            // We regret to inform you that your OpenModelica flowsheet and abstract with the following details have been disapproved:
            // 
            // Full Name: ' . $user_info->name_title . ' ' . $user_info->contributor_name . '
            // Project Title: ' . $user_info->project_title . '
            // Name of compound for which process development is carried out: ' . $user_info->process_development_compound_name . '
            // Reason for Disapproval / Feedback: ' . $form_state['values']['disapprove_message'] . '
            // 
            // You are requested to visit the Abstract and Flowsheet submission page at https://om.fossee.in/chemical/flowsheeting-project/abstract-code and re-upload the flowsheet and abstract.
            // 
            // Best Wishes,
            // 
            // !site_name Team
            // FOSSEE, IIT Bombay', array(
            // 							'!site_name' => variable_get('site_name', ''),
            // 							'!user_name' => $user_data->name
            // 						))
            // 					);

            /** sending email when everything done **/
            $email_to = $user_data->mail;
            $from = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_from_email');
            $bcc = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_emails');
            $cc = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_cc_emails');
            $params['standard']['subject'] = $email_subject;
            $params['standard']['body'] = $email_body;
            $params['standard']['headers'] = [
              'From' => $from,
              'MIME-Version' => '1.0',
              'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
              'Content-Transfer-Encoding' => '8Bit',
              'X-Mailer' => 'Drupal',
              'Cc' => $cc,
              'Bcc' => $bcc,
            ];
            if (!drupal_mail('om_flowsheet', 'standard', $email_to, language_default(), $params, $from, TRUE)) {
              \Drupal::messenger()->addError('Error sending email message.');
            } //!drupal_mail('om_flowsheet', 'standard', $email_to, language_default(), $params, $from, TRUE)
          } //$form_state['values']['flowsheet_actions'] == 2
          elseif ($form_state->getValue(['flowsheet_actions']) == 3) //disapprove and delete entire flowsheeting project
 {
            if (strlen(trim($form_state->getValue(['deletion_message']))) == 0) {
              $form_state->setErrorByName('deletion_message', t(''));
              $msg = \Drupal::messenger()->addError("Please mention the reason for deletion.");
              return $msg;
            }
            else {
              if (strlen(trim($form_state->getValue(['deletion_message']))) <= 30) {
                $form_state->setErrorByName('message', t(''));
                $msg = \Drupal::messenger()->addError("Please mention the reason for deletion. Minimum 30 character required");
                return $msg;
              }
            } //strlen(trim($form_state['values']['message'])) <= 30
            if (!\Drupal::currentUser()->hasPermission('om flowsheet bulk delete code')) {
              $msg = \Drupal::messenger()->addError(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Lab.'));
              return $msg;
            } //!user_access('flowsheet bulk delete code')
            if (om_flowsheet_abstract_delete_project($form_state->getValue(['flowsheet_project']))) //////
 {
              \Drupal::messenger()->addStatus(t('Dis-Approved and Deleted Entire Flowsheeting project.'));
              // @FIXME
              // // @FIXME
              // // This looks like another module's variable. You'll need to rewrite this call
              // // to ensure that it uses the correct configuration object.
              // $email_subject = t('[!site_name][Flowsheeting Project] Your uploaded OpenModelica Flowsheeting Project has been deleted with the proposal form', array(
              // 						'!site_name' => variable_get('site_name', '')
              // 						));

              // @FIXME
              // // @FIXME
              // // This looks like another module's variable. You'll need to rewrite this call
              // // to ensure that it uses the correct configuration object.
              // $email_body = array(
              // 						0 => t('
              // 
              // Dear ' . $user_info->contributor_name . ',
              // 
              // We regret to inform you that your OpenModelica flowsheet and abstract along with the proposal form with the following details have been deleted:
              // 
              // Full Name: ' . $user_info->name_title . ' ' . $user_info->contributor_name . '
              // Project Title: ' . $user_info->project_title . '
              // Name of compound for which process development is carried out: ' . $user_info->process_development_compound_name . '
              // Reason for dis-approval: ' . $form_state['values']['deletion_message'] . '
              // 
              // Now, you can propose a new flowsheet.
              // 
              // Best Wishes,
              // 
              // !site_name Team
              // FOSSEE, IIT Bombay', array(
              // 					'!site_name' => variable_get('site_name', ''),
              // 					'!user_name' => $user_data->name
              // 					))
              // 						);

              $email_to = $user_data->mail;
              $from = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_from_email');
              $bcc = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_emails');
              $cc = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_cc_emails');
              $params['standard']['subject'] = $email_subject;
              $params['standard']['body'] = $email_body;
              $params['standard']['headers'] = [
                'From' => $from,
                'MIME-Version' => '1.0',
                'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
                'Content-Transfer-Encoding' => '8Bit',
                'X-Mailer' => 'Drupal',
                'Cc' => $cc,
                'Bcc' => $bcc,
              ];
              if (!drupal_mail('om_flowsheet', 'standard', $email_to, language_default(), $params, $from, TRUE)) {
                \Drupal::messenger()->addError('Error sending email message.');
              }
            } //om_flowsheet_abstract_delete_project($form_state['values']['flowsheet_project'])
            else {
              \Drupal::messenger()->addError(t('Error Dis-Approving and Deleting Entire flowsheeting project.'));
            }
          }//$form_state['values']['flowsheet_actions'] == 3
          //$form_state['values']['flowsheet_actions'] == 4

        }
      } //user_access('flowsheet project bulk manage code')
      return $msg;
    } //$form_state['clicked_button']['#value'] == 'Submit'
  }

}
?>
