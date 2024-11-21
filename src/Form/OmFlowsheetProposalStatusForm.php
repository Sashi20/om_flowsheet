<?php

/**
 * @file
 * Contains \Drupal\om_flowsheet\Form\OmFlowsheetProposalStatusForm.
 */

namespace Drupal\om_flowsheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;



class OmFlowsheetProposalStatusForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'om_flowsheet_proposal_status_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id = \Drupal::routeMatch()->getParameter('proposal_id');
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
        return;
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      //drupal_goto('chemical/flowsheeting-project/manage-proposal');
      return;
    }
    $query = \Drupal::database()->select('om_flowsheet_library');
    $query->fields('om_flowsheet_library');
    $query->condition('id', $proposal_data->simulator_version_id);
    $result = $query->execute()->fetchObject();
    
    $simulator_version_name = Link::fromTextAndUrl(
  $result->simulator_version_name,
  Url::fromUri($result->link)
)->toString();

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
    //    'header' => $headers,
    //    'rows' => $rows
    //  ));
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
    $proposal_status = '';
    switch ($proposal_data->approval_status) {
      case 0:
        $proposal_status = t('Pending');
        break;
      case 1:
        $proposal_status = t('Approved');
        break;
      case 2:
        $proposal_status = t('Dis-approved');
        break;
      case 3:
        $proposal_status = t('Completed');
        break;
      default:
        $proposal_status = t('Unkown');
        break;
    } //$proposal_data->approval_status

    $form['proposal_status'] = [
      '#type' => 'item',
      '#markup' => $proposal_status,
      '#title' => t('Proposal Status'),
    ];
    if ($proposal_data->approval_status == 0) {
      $url = Url::fromUserInput('/chemical/flowsheeting-project/manage-proposal/approve/' . $proposal_id);

// Create the link.
$link = Link::fromTextAndUrl('Click here', $url)->toString();

$form['approve'] = array(
			'#type' => 'item',
			'#markup' => $link,
			'#title' => t('Approve')
		);

    } //$proposal_data->approval_status == 0
    if ($proposal_data->approval_status == 1) {
      $form['completed'] = [
        '#type' => 'checkbox',
        '#title' => t('Completed'),
        '#description' => t('Check if user has provided all the required files and pdfs.'),
      ];
    } //$proposal_data->approval_status == 1
    if ($proposal_data->approval_status == 2) {
      $form['message'] = [
        '#type' => 'item',
        '#markup' => $proposal_data->message,
        '#title' => t('Reason for disapproval'),
      ];
    } //$proposal_data->approval_status == 2
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $form['cancel'] = array(
    // 		'#type' => 'markup',
    // 		'#markup' => l(t('Cancel'), 'chemical/flowsheeting-project/manage-proposal/all')
    // 	);

    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id = \Drupal::routeMatch()->getParameter('proposal_id');
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
       // drupal_goto('chemical/flowsheeting-project/manage-proposal');
        return;
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      //drupal_goto('chemical/flowsheeting-project/manage-proposal');
      return;
    }
    /* set the book status to completed */
    if ($form_state->getValue(['completed']) == 1) {
      $up_query = "UPDATE om_flowsheet_proposal SET approval_status = :approval_status , actual_completion_date = :expected_completion_date WHERE id = :proposal_id";
      $args = [
        ":approval_status" => '3',
        ":proposal_id" => $proposal_id,
        ":expected_completion_date" => time(),
      ];
      $result = \Drupal::database()->query($up_query, $args);
      //CreateReadmeFileOpenModelicaFlowsheetingProject($proposal_id);
      if (!$result) {
        \Drupal::messenger()->addError('Error in update status');
        return;
      } //!$result
		/* sending email */
      $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
      $email_to = $user_data->getEmail();
      $from = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_from_email');
      $bcc = $user->getEmail() . ', ' . \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_emails');
      $cc = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_cc_emails');
      $params['om_flowsheet_proposal_completed']['proposal_id'] = $proposal_id;
      $params['om_flowsheet_proposal_completed']['user_id'] = $proposal_data->uid;
      $params['om_flowsheet_proposal_completed']['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];
      if (!drupal_mail('om_flowsheet', 'om_flowsheet_proposal_completed', $email_to, language_default(), $params, $from, TRUE)) {
        \Drupal::messenger()->addError('Error sending email message.');
      }
      \Drupal::messenger()->addStatus('Congratulations! om flowsheeting proposal has been marked as completed. User has been notified of the completion.');
    } //$form_state['values']['completed'] == 1
    drupal_goto('chemical/flowsheeting-project/manage-proposal');
    return;
  }

}
?>
