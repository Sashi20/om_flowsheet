<?php

/**
 * @file
 * Contains \Drupal\om_flowsheet\Form\OmFlowsheetProposalForm.
 */

namespace Drupal\om_flowsheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Database\Database;

class OmFlowsheetProposalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'om_flowsheet_proposal_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state)
  {
    $user = \Drupal::currentUser();
    //var_dump($user->uid);die;
    /************************ start approve book details ************************/
    if ($user->isAnonymous()) {
  // Create the error message with a link to the login page
  $msg = \Drupal::messenger()->addError(t('It is mandatory to ' . 
    \Drupal\Core\Link::fromTextAndUrl('login', \Drupal\Core\Url::fromRoute('user.page'))->toString() . 
    ' on this website to access the flowsheet proposal form. If you are a new user, please create a new account first.')
  );

  // Redirect to the login page
    $response = new RedirectResponse(Url::fromRoute('user.page')->toString());

  $response->send();
  
  // Return the error message (optional)
  return $msg;
}
    $query = \Drupal::database()->select('om_flowsheet_proposal');
    $query->fields('om_flowsheet_proposal');
    $query->condition('uid', $user->id());
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    if ($proposal_data) {
      if ($proposal_data->approval_status == 0 || $proposal_data->approval_status == 1) {
        \Drupal::messenger()->addError(t('We have already received your proposal.'));
          // Create a redirect response to the front page
  $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  
  // Send the redirect response
  $response->send();

        return;
      } //$proposal_data->approval_status == 0 || $proposal_data->approval_status == 1
    } //$proposal_data
    $imp = t('<span style="color: red;">*This is a mandatory field</span>');
    $form['#attributes'] = ['enctype' => "multipart/form-data"];
    $form['name_title'] = [
      '#type' => 'select',
      
'#title' => t('Title'),
      '#options' => [
        'Dr' => 'Dr',
        'Prof' => 'Prof',
        'Mr' => 'Mr',
        'Mrs' => 'Mrs',
        'Ms' => 'Ms',
      ],
      '#required' => TRUE,
    ];
    $form['contributor_name'] = [
      '#type' => 'textfield',
      
'#title' => t('Name of the contributor'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your full name.....')
        ],
      '#maxlength' => 250,
      '#required' => TRUE,
    ];
    $form['contributor_contact_no'] = [
      '#type' => 'textfield',
      
'#title' => t('Contact No.'),
      '#size' => 50,
      '#attributes' => [
        'placeholder' => t('Enter your contact number')
        ],
      '#maxlength' => 10,
    ];
    $form['month_year_of_degree'] = [
      '#type' => 'date',
      
'#title' => t('Month and year of award of degree'),
      '#date_label_position' => '',
      '#description' => '',
      '#default_value' => '',
      '#date_format' => 'm-Y',
      '#date_increment' => 0,
      '#date_year_range' => '1960: +22',
      '#required' => TRUE,
       '#datepicker_options' => array(
    'changeMonth' => TRUE, // Allows users to change the month.
    'changeYear' => TRUE, // Allows users to change the year.
    'showButtonPanel' => TRUE, // Adds a button to close the date picker.
    'dateFormat' => 'mm-yy', // Forces the format to month-year.
    'showMonthAfterYear' => TRUE, // Displays the month after the year.
    'yearRange' => '1960:+22', // Specifies the year range.
  ),
    ];
    $form['contributor_email_id'] = [
      '#type' => 'textfield',
      
'#title' => t('Email'),
      '#size' => 30,
      '#value' => $user->getEmail(),
      '#disabled' => TRUE,
    ];
    $form['university'] = [
      '#type' => 'textfield',
      
'#title' => t('University/ Institute'),
      '#size' => 80,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Insert full name of your institute/ university.... '
        ],
    ];
    $form['project_guide_name'] = [
      '#type' => 'textfield',
      
'#title' => t('Project guide'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter full name of project guide')
        ],
      '#maxlength' => 250,
    ];
    $form['project_guide_email_id'] = [
      '#type' => 'textfield',
      
'#title' => t('Project guide email'),
      '#size' => 100,
    ];
    $form['project_guide_university'] = [
      '#type' => 'textfield',
      
'#title' => t('Project Guide University/ Institute'),
      '#size' => 100,
      '#maxlength' => 200,
      '#attributes' => [
        'placeholder' => 'Insert full name of the institute/ university of your project guide.... '
        ],
    ];
    $form['country'] = [
      '#type' => 'select',
      
'#title' => t('Country'),
      '#options' => [
        'India' => 'India',
        'Others' => 'Others',
      ],
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
    ];
    $form['other_country'] = [
      '#type' => 'textfield',
      
'#title' => t('Other than India'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your country name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
      '#description' => $imp,
    ];
    $form['other_state'] = [
      '#type' => 'textfield',
      
'#title' => t('State other than India'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your state/region name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
      '#description' => $imp,
    ];
    $form['other_city'] = [
      '#type' => 'textfield',
      
'#title' => t('City other than India'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your city name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
      '#description' => $imp,
    ];
    $form['all_state'] = [
      '#type' => 'select',
      
'#title' => t('State'),
      '#options' => _om_df_list_of_states(),
      '#validated' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
      '#description' => $imp,
    ];
    $form['city'] = [
      '#type' => 'select',
      
'#title' => t('City'),
      '#options' => _om_df_list_of_cities(),
      '#validated' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
      '#description' => $imp,
    ];
    $form['pincode'] = [
      '#type' => 'textfield',
      
'#title' => t('Pincode'),
      '#size' => 30,
      '#maxlength' => 6,
      '#required' => FALSE,
      '#attributes' => [
        'placeholder' => 'Enter pincode....'
        ],
      '#required' => TRUE,
    ];
    /***************************************************************************/
    $form['hr'] = [
      '#type' => 'item',
      '#markup' => '<hr>',
    ];
    $form['dwsim_flowsheet_check'] = [
  '#type' => 'radios',
  '#title' => $this->t('Is the proposed OpenModelica flowsheet from the list of completed DWSIM Flowsheets?'),
  '#options' => [
    '1' => $this->t('Yes'),
    '0' => $this->t('No'),
  ],
];

$form['dwsim_flowsheet_name_dropdown'] = [
  '#type' => 'select',
  '#title' => $this->t('Select the name of flowsheet proposed.'),
  //'#required' => TRUE,
  '#options' => _df_list_of_dwsim_flowsheets(),
  '#states' => [
    'visible' => [
      ':input[name="dwsim_flowsheet_check"]' => ['value' => '1'],
    ],
  ],
];

$form['project_title'] = [
  '#type' => 'textfield',
  '#maxlength' => 250,
  '#title' => $this->t('Project Title'),
  '#size' => 100,
  '#description' => $this->t('Maximum character limit is 250'),
  '#required' => TRUE,
  '#states' => [
    'visible' => [
      ':input[name="dwsim_flowsheet_check"]' => ['value' => '0'],
    ],
  ],
];
    $form['reference'] = [
      '#type' => 'textfield',
      
'#title' => t('Reference'),
      '#size' => 100,
      '#maxlength' => 250,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Enter reference'
        ],
    ];
    $form['version'] = [
      '#type' => 'select',
      
'#title' => t('Version'),
      '#options' => _om_df_list_of_software_version(),
      '#required' => TRUE,
    ];
    $form['older'] = [
      '#type' => 'textfield',
      
'#title' => t('Other Version'),
      '#size' => 30,
      '#maxlength' => 50,
      //'#required' => TRUE,
		'#description' => t('Specify the Older version used as format "om v2.0"'),
      '#states' => [
        'visible' => [
          ':input[name="version"]' => [
            'value' => 'Old version'
            ]
          ]
        ],
    ];
    $form['process_development_compound_name'] = [
      '#type' => 'textfield',
      
'#title' => t('Name of compound for which process development is carried out'),
      '#size' => 50,
      '#description' => t('Mention the compound name as shown:
Ex: Ethanol'),
      '#required' => TRUE,
    ];
    $form['process_development_compound_cas_no'] = [
      '#type' => 'textfield',
      
'#title' => t('CAS number for compound which process development is carried out'),
      '#size' => 50,
      '#description' => t('Mention the compound CAS No as shown:
Ex: 64-17-5'),
      '#required' => TRUE,
    ];
    $form['simulator_version_used'] = [
      '#type' => 'select',
      
'#title' => t('Simulator version used for creating the flowsheet'),
      '#options' => _df_list_of_simulator_version_used(),
      '#required' => TRUE,
    ];

   /* $form['term_condition'] = [
      '#type' => 'checkboxes',
      
'#title' => t('Terms And Conditions'),
      '#options' => [
        'status' => t('<a href="/term-and-conditions" target="_blank">I agree to the Terms and Conditions</a>')
        ],
      '#required' => TRUE,
    ];*/
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),

    ];
    return $form;
  }
public function validateForm(array &$form, FormStateInterface $form_state) {
    /*$project_title = $form_state['values']['project_title'];
  $query = db_select('om_flowsheet_proposal');
  $query->fields('om_flowsheet_proposal');
  $query->condition('project_title', $project_title);
  $query->condition(db_or()->condition('approval_status',1)->condition('approval_status',3)); 
  $result = $query->execute()->rowCount();
  if ($result > 0)
  {
    form_set_error('project_title', t('Project title name already exists'));
    return;
  }*/
    /*$query = db_select('om_flowsheet_proposal');
  $query->fields('om_flowsheet_proposal');
  $query->condition('project_title', $project_title);
  $query->condition('approval_status',3); 
  $result1 = $query->execute()->rowCount();
  if ($result1 > 0)
  {
    form_set_error('project_title', t('Project title name already exists'));
    return;
  }*/
    if ($form_state->getValue(['term_condition']) == '1') {
      $form_state->setErrorByName('term_condition', t('Please check the terms and conditions'));
      // $form_state['values']['country'] = $form_state['values']['other_country'];
    } //$form_state['values']['term_condition'] == '1'
    if ($form_state->getValue([
      'country'
      ]) == 'Others') {
      if ($form_state->getValue(['other_country']) == '') {
        $form_state->setErrorByName('other_country', t('Enter country name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['other_country'] == ''
      else {
        $form_state->setValue(['country'], $form_state->getValue([
          'other_country'
          ]));
      }
      if ($form_state->getValue(['other_state']) == '') {
        $form_state->setErrorByName('other_state', t('Enter state name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['other_state'] == ''
      else {
        $form_state->setValue(['all_state'], $form_state->getValue([
          'other_state'
          ]));
      }
      if ($form_state->getValue(['other_city']) == '') {
        $form_state->setErrorByName('other_city', t('Enter city name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['other_city'] == ''
      else {
        $form_state->setValue(['city'], $form_state->getValue(['other_city']));
      }
    } //$form_state['values']['country'] == 'Others'
    else {
      if ($form_state->getValue(['country']) == '0') {
        $form_state->setErrorByName('country', t('Select country name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['country'] == ''
      if ($form_state->getValue([
        'all_state'
        ]) == '0') {
        $form_state->setErrorByName('all_state', t('Select state name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['all_state'] == ''
      if ($form_state->getValue([
        'city'
        ]) == '0') {
        $form_state->setErrorByName('city', t('Select city name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['city'] == ''
    }
    //Validation for project title
      /*if ($form_state->getValue([
        'dwsim_flowsheet_check'
        ]) == 1) {
        $project_title = $form_state->getValue(['dwsim_flowsheet_name_dropdown']);
      }
      else {

        $project_title = $form_state->getValue(['project_title']);
      }*/
    //var_dump($project_title);die;
    /*$query = \Drupal::database()->select('om_flowsheet_proposal');
    $query->fields('om_flowsheet_proposal');
    $query->condition('project_title', $project_title);
    $query->condition('approval_status', 2, '!=');
    $result = $query->execute()->rowCount();
    //var_dump($result);die;
    if ($result >= 1) {
      $form_state->setErrorByName('', t('Project title name already exists'));
      return;
    }*/
    /*if ($form_state->getValue(['project_title']) != '') {
      if (strlen($form_state->getValue(['project_title'])) > 250) {
        $form_state->setErrorByName('project_title', t('Maximum charater limit is 250 charaters only, please check the length of the project title'));
      } //strlen($form_state['values']['project_title']) > 250
      else {
        if (strlen($form_state->getValue(['project_title'])) < 10) {
          $form_state->setErrorByName('project_title', t('Minimum charater limit is 10 charaters, please check the length of the project title'));

        }
        else {
          if (preg_match('/[\^£$%&*()}{@#~?><>.:;`|=_+¬]/', $form_state->getValue([
            'project_title'
            ]))) {
            $form_state->setErrorByName('project_title', t('Special characters are not allowed for project title'));
          }
        }
      }
      //strlen($form_state['values']['project_title']) < 10
    }*/ //$form_state['values']['project_title'] != ''
  /*else
  {
    form_set_error('project_title', t('Project title shoud not be empty'));
  } */
    // validation for Name of compound for which process development is carried out
    $form_state->setValue([
      'process_development_compound_name'
      ], trim($form_state->getValue(['process_development_compound_name'])));
    if ($form_state->getValue(['process_development_compound_name']) != '') {
      if (strlen($form_state->getValue(['process_development_compound_name'])) >= 50) {
        $form_state->setErrorByName('process_development_compound_name', t('Maximum charater limit is 50 charaters only, please check the length'));
      } //strlen($form_state['values']['process_development_compound_name']) >= 50
      else {
        if (strlen($form_state->getValue(['process_development_compound_name'])) < 1) {
          $form_state->setErrorByName('process_development_compound_name', t('Minimum charater limit is 1 charaters, please check the length'));
        }
      } //strlen($form_state['values']['process_development_compound_name']) < 1
    } //$form_state['values']['process_development_compound_name'] != ''
    else {
      $form_state->setErrorByName('process_development_compound_name', t('Field should not be empty'));
    }
    $form_state->setValue(['process_development_compound_cas_no'], trim($form_state->getValue([
      'process_development_compound_cas_no'
      ])));
    if ($form_state->getValue(['process_development_compound_cas_no']) != '') {
      if (strlen($form_state->getValue(['process_development_compound_cas_no'])) < 1) {
        $form_state->setErrorByName('process_development_compound_cas_no', t('Minimum charater limit is 1 charaters, please check the length'));
      } //strlen($form_state['values']['process_development_compound_cas_no']) < 1
    } //$form_state['values']['process_development_compound_cas_no'] != ''
    else {
      $form_state->setErrorByName('process_development_compound_cas_no', t('CAS number field should not be empty'));
    }
    if ($form_state->getValue(['version']) == 'Old version') {
      if ($form_state->getValue(['older']) == '') {
        $form_state->setErrorByName('older', t('Please provide valid version'));
      } //$form_state['values']['older'] == ''
    } //$form_state['values']['version'] == 'Old version'
  /*if ($form_state['values']['om_database_compound_name'])
  {
    $om_database_compound_name = implode("| ", $_POST['om_database_compound_name']);
    $form_state['values']['om_database_compound_name'] = trim($om_database_compound_name);
  } *///$form_state['values']['om_database_compound_name']
    return;
  }
   public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    if (!$user->isAnonymous()) {
      \Drupal::messenger()->addError('It is mandatory to login on this website to access the proposal form');
      //return;
    } //!$user->uid
    /*if ($form_state->getValue(['version']) == 'Old version') {
      $form_state->setValue(['version'], trim($form_state->getValue(['older'])));
    } *///$form_state['values']['version'] == 'Old version'
	/* inserting the user proposal */
    $v = $form_state->getValues();
    //var_dump($v);die;
    $simulator_version_used = $v['simulator_version_used'];
    $query = \Drupal::database()->select('om_flowsheet_library');
    $query->fields('om_flowsheet_library');
    $query->condition('simulator_version_name', $simulator_version_used);
    $result = $query->execute()->fetchObject();
    $simulator_version_used_id = $result->id;
    //var_dump($v['dwsim_flowsheet_check']);die;
    if ($v['dwsim_flowsheet_check'] == 1) {
      $project_title = $v['dwsim_flowsheet_name_dropdown'];
      $dwsim_flowsheet_check = 1;
    }
    else {
      $dwsim_flowsheet_check = 0;
      $project_title = $v['project_title'];
    }

    //$project_title = trim($v['project_title']);
    $proposar_name = trim($v['name_title'] . ' ' . $v['contributor_name']);
    $university = trim($v['university']);
    $month_year_of_degree = $v['month_year_of_degree'];
    $directory_name = _om_df_dir_name($project_title, $proposar_name);
    $result = "INSERT INTO om_flowsheet_proposal
    (
    uid, 
    approver_uid,
    name_title, 
    contributor_name,
    contact_no,
    month_year_of_degree, 
    university,
    city, 
    pincode, 
    state, 
    country,
    version, 
    project_guide_name,
    project_guide_email_id,
    project_guide_university,
    project_title, 
    dwsim_flowsheet_check,
    process_development_compound_name, 
    process_development_compound_cas_number,
    simulator_version_id,
    approval_status,
    is_completed, 
    dissapproval_reason,
    creation_date, 
    approval_date, 
    directory_name,
   	reference
    ) VALUES
    (
    :uid, 
    :approver_uid, 
    :name_title, 
    :contributor_name, 
    :contact_no,
    :month_year_of_degree, 
    :university, 
    :city, 
    :pincode, 
    :state, 
    :country,
    :version, 
    :project_guide_name,
    :project_guide_email_id,
    :project_guide_university,
    :project_title, 
    :dwsim_flowsheet_check,
    :process_development_compound_name, 
    :process_development_compound_cas_number,
    :simulator_version_id,
    :approval_status,
    :is_completed, 
    :dissapproval_reason,
    :creation_date, 
    :approval_date, 
    :directory_name,
    :reference
    )";

    $args = [
      ":uid" => $user->id(),
      ":approver_uid" => 0,
      ":name_title" => $v['name_title'],
      ":contributor_name" => trim($v['contributor_name']),
      ":contact_no" => $v['contributor_contact_no'],
      ":month_year_of_degree" => $month_year_of_degree,
      ":university" => trim($v['university']),
      ":city" => $v['city'],
      ":pincode" => $v['pincode'],
      ":state" => $v['all_state'],
      ":country" => $v['country'],
      ":version" => $v['version'],
      ":project_guide_name" => $v['project_guide_name'],
      ":project_guide_email_id" => trim($v['project_guide_email_id']),
      ":project_guide_university" => trim($v['project_guide_university']),
     ":project_title" => $project_title,
     ":dwsim_flowsheet_check" => $dwsim_flowsheet_check,
      ":dwsim_flowsheet_name" => $v['dwsim_flowsheet_name'],
		":process_development_compound_name" => $v['process_development_compound_name'],
      ":process_development_compound_cas_number" => $v['process_development_compound_cas_no'],
      ":simulator_version_id" => $simulator_version_used_id,
      ":approval_status" => 0,
      ":is_completed" => 0,
      ":dissapproval_reason" => "NULL",
      ":creation_date" => time(),
      ":approval_date" => 0,
      ":directory_name" => $directory_name,
      ":reference" => $v['reference'],
    ];
    //var_dump($args);die;
    $connection = Database::getConnection();
$proposal_id= $connection->insert('om_flowsheet_proposal')->fields($args)->execute();
    //$proposal_id = \Drupal::database()->query($result, $args, $result);

    if (!$proposal_id) {
      \Drupal::messenger()->addError(t('Error receiving your proposal. Please try again.'));
      return;
    } //!$proposal_id
	/* sending email */
    /*$email_to = $user->getEmail();
    $form = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_from_email');
    $bcc = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_emails');
    $cc = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_cc_emails');
    $params['om_flowsheet_proposal_received']['proposal_id'] = $proposal_id;
    $params['om_flowsheet_proposal_received']['user_id'] = $user->uid;
    $params['om_flowsheet_proposal_received']['headers'] = [
      'From' => $form,
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
      'Cc' => $cc,
      'Bcc' => $bcc,
    ];*/
    /*if (!drupal_mail('om_flowsheet', 'om_flowsheet_proposal_received', $email_to, user_preferred_language($user), $params, $form, TRUE)) {
      \Drupal::messenger()->addError('Error sending email message.');
    }*/
    \Drupal::messenger()->addStatus(t('We have received your OpenModelica Flowsheeting proposal. We will get back to you soon.'));
    $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  
  // Send the redirect response
  $response->send();
  }

}
?>
