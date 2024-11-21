<?php

/**
 * @file
 * Contains \Drupal\om_flowsheet\Form\OmFlowsheetProposalApprovalForm.
 */

namespace Drupal\om_flowsheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;

class OmFlowsheetProposalApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'om_flowsheet_proposal_approval_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
  //  $route_match = \Drupal::routeMatch();

$proposal_id = \Drupal::routeMatch()->getParameter('proposal_id');
//var_dump($proposal_id);die;
    //$proposal_q = db_query("SELECT * FROM {om_flowsheet_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('om_flowsheet_proposal');
    $query->fields('om_flowsheet_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      } //$proposal_data = $proposal_q->fetchObject()
      else {
        \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
        $response = new RedirectResponse(Url::fromRoute('om_flowsheet.proposal_pending')->toString());
  
  // Send the redirect response
  $response->send();
        //drupal_goto('chemical/flowsheeting-project/manage-proposal');
        return;
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      $response = new RedirectResponse(Url::fromRoute('om_flowsheet.proposal_pending')->toString());
  
  // Send the redirect response
  $response->send();
      return;
    }
 // var_dump($proposal_data);die;
    if ($proposal_data->project_guide_name == "NULL" || $proposal_data->project_guide_name == "") {
      $project_guide_name = "Not Entered";
    } //$proposal_data->project_guide_name == NULL
    else {
      $project_guide_name = $proposal_data->project_guide_name;
    }
    if ($proposal_data->project_guide_email_id == "NULL" || $proposal_data->project_guide_email_id == "") {
      $project_guide_email_id = "Not Entered";
    } //$proposal_data->project_guide_email_id == NULL
    else {
      $project_guide_email_id = $proposal_data->project_guide_email_id;
    }
    $query = \Drupal::database()->select('om_flowsheet_library');
    $query->fields('om_flowsheet_library');
    $query->condition('id', $proposal_data->simulator_version_id);
    $result = $query->execute()->fetchObject();
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
     $simulator_version_name = Link::fromTextAndUrl(
  $result->simulator_version_name,
  Url::fromUri($result->link)
)->toString();

    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    $form['contributor_name'] = array(
    		'#type' => 'item',
    		'#markup' => Link::fromTextAndUrl(
  $proposal_data->name_title . ' ' . $proposal_data->contributor_name,
  Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid])
)->toString(),
    		'#size' => 250,
    		'#title' => t('Student name')
    	);
$user = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
$email = $user ? $user->getEmail() : NULL;
    $form['student_email_id'] = [
      '#title' => t('Student Email'),
      '#type' => 'item',
      '#markup' => $email,
      '#title' => t('Email'),
    ];
    $form['contributor_contact_no'] = [
      '#title' => t('Contact No.'),
      '#type' => 'item',
      '#markup' => $proposal_data->contact_no,
    ];
    $form['month_year_of_degree'] = [
      '#type' => 'date_popup',
      '#title' => t('Month and year of award of degree'),
      '#date_label_position' => '',
      '#description' => '',
      '#default_value' => $proposal_data->month_year_of_degree,
      '#date_format' => 'M-Y',
      '#date_increment' => 0,
      '#date_year_range' => '1960:+0',
      '#datepicker_options' => [
        'maxDate' => 0
        ],
      '#disabled' => TRUE,
    ];
    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->university,
      '#title' => t('University/Institute'),
    ];
    $form['country'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->country,
      '#title' => t('Country'),
    ];
    $form['all_state'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->state,
      '#title' => t('State'),
    ];
    $form['city'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->city,
      '#title' => t('City'),
    ];
    $form['pincode'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->pincode,
      '#title' => t('Pincode/Postal code'),
    ];
    $form['version'] = [
      '#type' => 'item',
      '#title' => t('om version'),
      '#markup' => $proposal_data->version,
    ];
    $form['project_guide_name'] = [
      '#type' => 'item',
      '#title' => t('Project guide'),
      '#markup' => $project_guide_name,
    ];
    $form['project_guide_email_id'] = [
      '#type' => 'item',
      '#title' => t('Project guide email'),
      '#markup' => $project_guide_email_id,
    ];
    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->project_title,
      '#title' => t('Title of the Flowsheet Project'),
    ];
    /*$form['dwsim_flowsheet_name'] = array(
		'#type' => 'item',
		'#title' => t('Name of the DWSIM flowsheet'),
		'#markup' => $proposal_data->dwsim_flowsheet_name,
	);*/
    $headers = [
      "User defined compound",
      "CAS No.",
    ];
    $rows = [];
    $item = [
      "{$proposal_data->process_development_compound_name}",
      "{$proposal_data->process_development_compound_cas_number}"
    ];
    array_push($rows, $item);
    // @FIXME
    // theme() has been renamed to _theme() and should NEVER be called directly.
    // Calling _theme() directly can alter the expected output and potentially
    // introduce security issues (see https://www.drupal.org/node/2195739). You
    // should use renderable arrays instead.
    // 
    // 
    // @see https://www.drupal.org/node/2195739
    // $prodata = theme('table', array(
    // 		'header' => $headers,
    // 		'rows' => $rows
    // 	));
    $prodata =  [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => 'no rows found',
    ];

    /*$form['process_development_compound_name'] = [
      '#type' => 'item',
      '#title' => t('Name of compound for which process development is carried out'),
      '#markup' => $prodata,
    ];*/
    $form['simulator_version_used'] = [
      '#type' => 'item',
      '#title' => t('Simulator version used for creating the flowsheet'),
      '#markup' => $simulator_version_name,
    ];
    $form['reference'] = [
      '#type' => 'item',
      '#title' => t('Reference'),
      '#markup' => $proposal_data->reference,
    ];
    /*
	$form['process_development_compound_cas_number'] = array(
	'#type' => 'item',
	'#title' => t('CAS Number of compound for which process development is carried out'),
	'#markup' => $proposal_data->process_development_compound_cas_number
	);*/
    $form['approval'] = [
      '#type' => 'radios',
      '#title' => t('Select to approve/disapprove the proposal'),
      '#options' => [
        '1' => 'Approve',
        '2' => 'Disapprove',
      ],
      '#required' => TRUE,
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => t('Reason for disapproval'),
      '#attributes' => [
        'placeholder' => t('Enter reason for disapproval in minimum 30 characters '),
        'cols' => 50,
        'rows' => 4,
      ],
      '#states' => [
        'visible' => [
          ':input[name="approval"]' => [
            'value' => '2'
            ]
          ]
        ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $form['cancel'] = array(
    // 		'#type' => 'item',
    // 		'#markup' => l(t('Cancel'), 'chemical/flowsheeting-project/manage-proposal')
    // 	);

    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->getValue(['approval']) == 2) {
      if ($form_state->getValue(['message']) == '') {
        $form_state->setErrorByName('message', t('Reason for disapproval could not be empty'));
      } //$form_state['values']['message'] == ''
    } //$form_state['values']['approval'] == 2
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id = \Drupal::routeMatch()->getParameter('proposal_id');
    // $proposal_q = db_query("SELECT * FROM {om_flowsheet_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('om_flowsheet_proposal');
    $query->fields('om_flowsheet_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      } //$proposal_data = $proposal_q->fetchObject()
      else {
        \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
        drupal_goto('chemical/flowsheeting-project/manage-proposal');
        return;
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      drupal_goto('chemical/flowsheeting-project/manage-proposal');
      return;
    }
    if ($form_state->getValue(['approval']) == 1) {
      $query = "UPDATE {om_flowsheet_proposal} SET approver_uid = :uid, approval_date = :date, approval_status = 1 WHERE id = :proposal_id";
      $args = [
        ":uid" => $user->uid,
        ":date" => time(),
        ":proposal_id" => $proposal_id,
      ];
      \Drupal::database()->query($query, $args);
      /* sending email */
      $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
      $email_to = $user_data->mail;
      $from = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_from_email');
      $bcc = $user->mail . ', ' . \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_emails');
      $cc = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_cc_emails');
      $params['om_flowsheet_proposal_approved']['proposal_id'] = $proposal_id;
      $params['om_flowsheet_proposal_approved']['user_id'] = $proposal_data->uid;
      $params['om_flowsheet_proposal_approved']['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];
      /*if (!drupal_mail('om_flowsheet', 'om_flowsheet_proposal_approved', $email_to, language_default(), $params, $from, TRUE)) {
        \Drupal::messenger()->addError('Error sending email message.');
      }*/
      \Drupal::messenger()->addStatus('om flowsheeting proposal No. ' . $proposal_id . ' approved. User has been notified of the approval.');
      $response = new RedirectResponse(Url::fromRoute('om_flowsheet.proposal_pending')->toString());
  
  // Send the redirect response
  $response->send();
      //drupal_goto('chemical/flowsheeting-project/manage-proposal');
      return;
    } //$form_state['values']['approval'] == 1
    else {
      if ($form_state->getValue(['approval']) == 2) {
        $query = "UPDATE {om_flowsheet_proposal} SET approver_uid = :uid, approval_date = :date, approval_status = 2, dissapproval_reason = :dissapproval_reason WHERE id = :proposal_id";
        $args = [
          ":uid" => $user->uid,
          ":date" => time(),
          ":dissapproval_reason" => $form_state->getValue(['message']),
          ":proposal_id" => $proposal_id,
        ];
        $result = \Drupal::database()->query($query, $args);
        /* sending email */
        $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
        $email_to = $user_data->mail;
        $from = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_from_email');
        $bcc = $user->mail . ', ' . \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_emails');
        $cc = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_cc_emails');
        $params['om_flowsheet_proposal_disapproved']['proposal_id'] = $proposal_id;
        $params['om_flowsheet_proposal_disapproved']['user_id'] = $proposal_data->uid;
        $params['om_flowsheet_proposal_disapproved']['headers'] = [
          'From' => $from,
          'MIME-Version' => '1.0',
          'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
          'Content-Transfer-Encoding' => '8Bit',
          'X-Mailer' => 'Drupal',
          'Cc' => $cc,
          'Bcc' => $bcc,
        ];
        if (!drupal_mail('om_flowsheet', 'om_flowsheet_proposal_disapproved', $email_to, language_default(), $params, $from, TRUE)) {
          \Drupal::messenger()->addError('Error sending email message.');
        }
        \Drupal::messenger()->addError('om flowsheeting proposal No. ' . $proposal_id . ' dis-approved. User has been notified of the dis-approval.');
        drupal_goto('chemical/flowsheeting-project/manage-proposal');
        return;
      }
    } //$form_state['values']['approval'] == 2
  }

}
?>
