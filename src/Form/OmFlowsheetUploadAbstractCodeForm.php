<?php

/**
 * @file
 * Contains \Drupal\om_flowsheet\Form\OmFlowsheetUploadAbstractCodeForm.
 */

namespace Drupal\om_flowsheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class OmFlowsheetUploadAbstractCodeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'om_flowsheet_upload_abstract_code_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $form['#attributes'] = ['enctype' => "multipart/form-data"];
    /* get current proposal */
    //$proposal_id = (int) arg(3);
    $uid = $user->uid;
    //$proposal_q = db_query("SELECT * FROM {om_flowsheet_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('om_flowsheet_proposal');
    $query->fields('om_flowsheet_proposal');
    $query->condition('uid', $uid);
    $query->condition('approval_status', '1');
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      } //$proposal_data = $proposal_q->fetchObject()
      else {
        \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
        drupal_goto('chemical/flowsheeting-project/abstract-code');
        return;
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      drupal_goto('chemical/flowsheeting-project/abstract-code');
      return;
    }
    $query = \Drupal::database()->select('om_flowsheet_submitted_abstracts');
    $query->fields('om_flowsheet_submitted_abstracts');
    $query->condition('proposal_id', $proposal_data->id);
    $abstracts_q = $query->execute()->fetchObject();
    if ($abstracts_q) {
      if ($abstracts_q->is_submitted == 1) {
        \Drupal::messenger()->addError(t('Your abstract is under review, you can not edit exisiting abstract without reviewer permission.'));
        drupal_goto('chemical/flowsheeting-project/abstract-code');
        //return;
      } //$abstracts_q->is_submitted == 1
    } //$abstracts_q->is_submitted == 1
    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->project_title,
      '#title' => t('Title of the Flowsheet Project'),
    ];
    $form['version'] = [
      '#type' => 'item',
      '#title' => t('OpenModelica version'),
      '#markup' => $proposal_data->version,
    ];
    if ($abstracts_q == TRUE) {
      if ($abstracts_q->unit_operations_used_in_om) {
        $existing_unit_operations_used_in_om = default_value_for_selections("unit_operations_used_in_om", $proposal_data->id);
        $form['unit_operations_used_in_om'] = [
          '#type' => 'select',
          '#title' => t('Unit Operations used in om'),
          '#options' => _df_list_of_unit_operations(),
          '#required' => TRUE,
          '#default_value' => $existing_unit_operations_used_in_om,
          '#size' => '20',
          '#multiple' => TRUE,
          '#description' => t('[You can select multiple options by holding ctrl + left key of mouse]'),
        ];
      } //$abstracts_q->unit_operations_used_in_om
    } //$abstracts_q->unit_operations_used_in_om
    else {
      $form['unit_operations_used_in_om'] = [
        '#type' => 'select',
        '#title' => t('Unit Operations used in om'),
        '#options' => _df_list_of_unit_operations(),
        '#required' => TRUE,
        '#size' => '20',
        '#multiple' => TRUE,
        '#description' => t('[You can select multiple options by holding ctrl + left key of mouse]'),
      ];
    }
    if ($abstracts_q == TRUE) {
      if ($abstracts_q->thermodynamic_packages_used) {
        $existing_thermodynamic_packages_used = default_value_for_selections("thermodynamic_packages_used", $proposal_data->id);
        $form['thermodynamic_packages_used'] = [
          '#type' => 'select',
          '#title' => t('Thermodynamic Packages Used'),
          '#options' => _df_list_of_thermodynamic_packages(),
          '#required' => TRUE,
          '#size' => '20',
          '#default_value' => $existing_thermodynamic_packages_used,
          '#multiple' => TRUE,
          '#description' => t('[You can select multiple options by holding ctrl + left key of mouse]'),
        ];
      } //$abstracts_q->thermodynamic_packages_used
    } //$abstracts_q == TRUE
    else {
      $form['thermodynamic_packages_used'] = [
        '#type' => 'select',
        '#title' => t('Thermodynamic Packages Used'),
        '#options' => _df_list_of_thermodynamic_packages(),
        '#required' => TRUE,
        '#size' => '20',
        '#multiple' => TRUE,
        '#description' => t('[You can select multiple options by holding ctrl + left key of mouse]'),
      ];
    }

    $headers = [
      "Name of compound for which process development is carried out",
      "CAS No.",
    ];
    $rows = [];
    $item = [
      "{$proposal_data->process_development_compound_name}",
      "{$proposal_data->process_development_compound_cas_number}"
      ,
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

    $form['process_development_compound_name'] = [
      '#type' => 'item',
      '#title' => t('Name of compound for which process development is carried out'),
      '#markup' => $prodata,
    ];

    $existing_uploaded_A_file = default_value_for_uploaded_files("A", $proposal_data->id);
    if (!$existing_uploaded_A_file) {
      $existing_uploaded_A_file = new stdClass();
      $existing_uploaded_A_file->filename = "No file uploaded";
    } //!$existing_uploaded_A_file
    $form['upload_an_abstract'] = [
      '#type' => 'file',
      '#title' => t('Upload an abstract (brief outline) of the project.'),
      '#description' => t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_A_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_abstract_upload_extensions') . '</span>',
    ];
    $existing_uploaded_S_file = default_value_for_uploaded_files("S", $proposal_data->id);
    if (!$existing_uploaded_S_file) {
      $existing_uploaded_S_file = new stdClass();
      $existing_uploaded_S_file->filename = "No file uploaded";
    } //!$existing_uploaded_S_file
    $form['upload_flowsheet_developed_process'] = [
      '#type' => 'file',
      '#title' => t('Upload the OpenModelica flowsheet for the developed process.'),
      '#description' => t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_S_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_flowsheet_developed_process_source_extensions') . '</span>',
    ];
    /*$existing_uploaded_P_file = default_value_for_uploaded_files("P", $proposal_data->id);
	if (!$existing_uploaded_P_file)
	{
		$existing_uploaded_P_file = new stdClass();
		$existing_uploaded_P_file->filename = "No file uploaded";
	} //!$existing_uploaded_S_file
	$form['upload_simulator_package'] = array(
		'#type' => 'file',
		'#title' => t('Upload the Simulator package used to simulate the flowsheet'),
		'#description' => t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_P_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . variable_get('om_flowsheet_simulator_package_extensions', '') . '</span>'
	);*/
    $query = \Drupal::database()->select('om_flowsheet_library');
    $query->fields('om_flowsheet_library');
    $query->condition('id', $proposal_data->simulator_version_id);
    $result = $query->execute()->fetchObject();
    $simulator_version_used_name = $result->simulator_version_name;
    $form['simulator_version_used'] = [
      '#type' => 'select',
      '#title' => t('Select the Simulator package used to simulate the flowsheet'),
      '#options' => _df_list_of_simulator_version_used(),
      '#required' => TRUE,
      '#default_value' => $simulator_version_used_name,
    ];
    $form['prop_id'] = [
      '#type' => 'hidden',
      '#value' => $proposal_data->id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#submit' => [
        'om_flowsheet_upload_abstract_code_form_submit'
        ],
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
    if ($form_state->getValue(['unit_operations_used_in_om'])) {
      $unit_operations_used_in_om = implode(", ", $_POST['unit_operations_used_in_om']);
      $form_state->setValue(['unit_operations_used_in_om'], $unit_operations_used_in_om);
    } //$form_state['values']['unit_operations_used_in_om']
    else {
      $form_state->setErrorByName('unit_operations_used_in_om', t('Please select.'));
    }
    if ($form_state->getValue(['thermodynamic_packages_used'])) {
      $thermodynamic_packages_used = implode(", ", $_POST['thermodynamic_packages_used']);
      $form_state->setValue(['thermodynamic_packages_used'], $thermodynamic_packages_used);
    } //$form_state['values']['thermodynamic_packages_used']
    else {
      $form_state->setErrorByName('thermodynamic_packages_used', t('Please select.'));
    }
    if (isset($_FILES['files'])) {
      /* check if file is uploaded */
      $existing_uploaded_A_file = default_value_for_uploaded_files("A", $form_state->getValue([
        'prop_id'
        ]));
      $existing_uploaded_S_file = default_value_for_uploaded_files("S", $form_state->getValue([
        'prop_id'
        ]));
      /*$existing_uploaded_P_file = default_value_for_uploaded_files("P", $form_state['values']['prop_id']); */
      if (!$existing_uploaded_S_file) {
        if (!($_FILES['files']['name']['upload_flowsheet_developed_process'])) {
          $form_state->setErrorByName('upload_flowsheet_developed_process', t('Please upload the file.'));
        }
      } //!$existing_uploaded_S_file
      if (!$existing_uploaded_A_file) {
        if (!($_FILES['files']['name']['upload_an_abstract'])) {
          $form_state->setErrorByName('upload_an_abstract', t('Please upload the file.'));
        }
      } //!$existing_uploaded_A_file
		/*if (!$existing_uploaded_P_file)
		{
			if (!($_FILES['files']['name']['upload_simulator_package']))
				form_set_error('upload_simulator_package', t('Please upload the file.'));
		} */
      /* check for valid filename extensions */
      if ($_FILES['files']['name']['upload_an_abstract'] || $_FILES['files']['name']['upload_flowsheet_developed_process'] || $_FILES['files']['name']['upload_simulator_package']) {
        foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
          if ($file_name) {
            /* checking file type */
            if (strstr($file_form_name, 'upload_flowsheet_developed_process')) {
              $file_type = 'S';
            }
            else {
              if (strstr($file_form_name, 'upload_an_abstract')) {
                $file_type = 'A';
              }
                //else if (strstr($file_form_name, 'upload_simulator_package'))
                //	$file_type = 'P';
              else {
                $file_type = 'U';
              }
            }
            $allowed_extensions_str = '';
            switch ($file_type) {
              case 'S':
                $allowed_extensions_str = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_flowsheet_developed_process_source_extensions');
                break;
              case 'A':
                $allowed_extensions_str = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_abstract_upload_extensions');
                break;
              /*case 'P':
							$allowed_extensions_str = variable_get('om_flowsheet_simulator_package_extensions', '');
							break;*/
            } //$file_type
            $allowed_extensions = explode(',', $allowed_extensions_str);
            $tmp_ext = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
            $temp_extension = end($tmp_ext);
            if (!in_array($temp_extension, $allowed_extensions)) {
              $form_state->setErrorByName($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
            }
            if ($_FILES['files']['size'][$file_form_name] <= 0) {
              $form_state->setErrorByName($file_form_name, t('File size cannot be zero.'));
            }
            /* check if valid file name */
            if (!om_flowsheet_check_valid_filename($_FILES['files']['name'][$file_form_name])) {
              $form_state->setErrorByName($file_form_name, t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
            }
          } //$file_name
        } //$_FILES['files']['name'] as $file_form_name => $file_name
      } //$_FILES['files']['name'] as $file_form_name => $file_name
    } //isset($_FILES['files'])
    // drupal_add_js('jQuery(document).ready(function () { alert("Hello!"); });', 'inline');
    // drupal_static_reset('drupal_add_js') ;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $v = $form_state->getValues();
    //var_dump($simulator_version_used_id);die;
    $root_path = om_flowsheet_path();
    $proposal_data = om_flowsheet_get_proposal();
    $proposal_id = $proposal_data->id;
    if (!$proposal_data) {
      drupal_goto('chemical');
      return;
    } //!$proposal_data
    $proposal_id = $proposal_data->id;
    $proposal_directory = $proposal_data->directory_name;
    /* create proposal folder if not present */
    $dest_path = $proposal_directory . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }
    $proposal_id = $proposal_data->id;
    $query_s = "SELECT * FROM {om_flowsheet_submitted_abstracts} WHERE proposal_id = :proposal_id";
    $args_s = [":proposal_id" => $proposal_id];
    $simulator_version_used = $v['simulator_version_used'];
    //var_dump($simulator_version_used);die;
    $query = \Drupal::database()->select('om_flowsheet_library');
    $query->fields('om_flowsheet_library');
    $query->condition('simulator_version_name', $simulator_version_used);
    $result = $query->execute()->fetchObject();
    $simulator_version_used_id = $result->id;
    $query_s_result = \Drupal::database()->query($query_s, $args_s)->fetchObject();
    if (!$query_s_result) {
      /* creating solution database entry */
      $query = "INSERT INTO {om_flowsheet_submitted_abstracts} (
	proposal_id,
	approver_uid,
	abstract_approval_status,
	unit_operations_used_in_om,
	thermodynamic_packages_used,
	abstract_upload_date,
	abstract_approval_date,
	is_submitted) VALUES (:proposal_id, :approver_uid, :abstract_approval_status, :unit_operations_used_in_om, 
  :thermodynamic_packages_used, :abstract_upload_date, :abstract_approval_date, :is_submitted)";
      $args = [
        ":proposal_id" => $proposal_id,
        ":approver_uid" => 0,
        ":abstract_approval_status" => 0,
        ":unit_operations_used_in_om" => $v['unit_operations_used_in_om'],
        ":thermodynamic_packages_used" => $v['thermodynamic_packages_used'],
        ":abstract_upload_date" => time(),
        ":abstract_approval_date" => 0,
        ":is_submitted" => 1,
      ];
      $submitted_abstract_id = \Drupal::database()->query($query, $args, $query);
      $query1 = "UPDATE {om_flowsheet_proposal} SET simulator_version_id = :simulator_version_id, is_submitted = :is_submitted WHERE id = :id";
      $args1 = [
        ":is_submitted" => 1,
        ":simulator_version_id" => $simulator_version_used_id,
        ":id" => $proposal_id,
      ];
      \Drupal::database()->query($query1, $args1);
      \Drupal::messenger()->addStatus('Abstract uploaded successfully.');
    } //!$query_s_result
    else {
      $query = "UPDATE {om_flowsheet_submitted_abstracts} SET 
	unit_operations_used_in_om=  :unit_operations_used_in_om,
	thermodynamic_packages_used= :thermodynamic_packages_used,
	abstract_upload_date =:abstract_upload_date,
	is_submitted= :is_submitted 
	WHERE proposal_id = :proposal_id
	";
      $args = [
        ":unit_operations_used_in_om" => $v['unit_operations_used_in_om'],
        ":thermodynamic_packages_used" => $v['thermodynamic_packages_used'],
        ":abstract_upload_date" => time(),
        ":is_submitted" => 1,
        ":proposal_id" => $proposal_id,
      ];
      $query1 = "UPDATE {om_flowsheet_proposal} SET simulator_version_id = :simulator_version_id, is_submitted = :is_submitted WHERE id = :id";
      $args1 = [
        ":is_submitted" => 1,
        ":simulator_version_id" => $simulator_version_used_id,
        ":id" => $proposal_id,
      ];
      \Drupal::database()->query($query1, $args1);
      $submitted_abstract_id = \Drupal::database()->query($query, $args, $query);
      \Drupal::messenger()->addStatus('Abstract updated successfully.');
    }
    /* uploading files */
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
      if ($file_name) {
        /* checking file type */
        if (strstr($file_form_name, 'upload_flowsheet_developed_process')) {
          $file_type = 'S';
        } //strstr($file_form_name, 'upload_flowsheet_developed_process')
        else {
          if (strstr($file_form_name, 'upload_an_abstract')) {
            $file_type = 'A';
          } //strstr($file_form_name, 'upload_an_abstract')
			/*else if (strstr($file_form_name, 'upload_simulator_package'))
			{
				$file_type = 'P';
			} */
          else {
            $file_type = 'U';
          }
        }
        switch ($file_type) {
          case 'S':
            if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
              //unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
              move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
              \Drupal::messenger()->addError(t("File !filename already exists hence overwirtten the exisitng file ", [
                '!filename' => $_FILES['files']['name'][$file_form_name]
                ]));
            } //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
					/* uploading file */
            else {
              if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
                /* for uploaded files making an entry in the database */
                $query_ab_f = "SELECT * FROM om_flowsheet_submitted_abstracts_file WHERE proposal_id = :proposal_id AND filetype = 
				:filetype";
                $args_ab_f = [
                  ":proposal_id" => $proposal_id,
                  ":filetype" => $file_type,
                ];
                $query_ab_f_result = \Drupal::database()->query($query_ab_f, $args_ab_f)->fetchObject();
                if (!$query_ab_f_result) {
                  $query = "INSERT INTO {om_flowsheet_submitted_abstracts_file} (submitted_abstract_id, proposal_id, uid, approvar_uid, filename, filepath, filemime, filesize, filetype, timestamp)
          VALUES (:submitted_abstract_id, :proposal_id, :uid, :approvar_uid, :filename, :filepath, :filemime, :filesize, :filetype, :timestamp)";
                  $args = [
                    ":submitted_abstract_id" => $submitted_abstract_id,
                    ":proposal_id" => $proposal_id,
                    ":uid" => $user->uid,
                    ":approvar_uid" => 0,
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":filetype" => $file_type,
                    ":timestamp" => time(),
                  ];
                  \Drupal::database()->query($query, $args);
                  \Drupal::messenger()->addStatus($file_name . ' uploaded successfully.');
                } //!$query_ab_f_result
                else {
                  unlink($root_path . $dest_path . $query_ab_f_result->filename);
                  $query = "UPDATE {om_flowsheet_submitted_abstracts_file} SET filename = :filename, filepath=:filepath, filemime=:filemime, filesize=:filesize, timestamp=:timestamp WHERE proposal_id = :proposal_id AND filetype = :filetype";
                  $args = [
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $file_path . $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":timestamp" => time(),
                    ":proposal_id" => $proposal_id,
                    ":filetype" => $file_type,
                  ];
                  \Drupal::database()->query($query, $args);
                  \Drupal::messenger()->addStatus($file_name . ' file updated successfully.');
                }
              } //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
              else {
                \Drupal::messenger()->addError('Error uploading file : ' . $dest_path . $file_name);
              }
            }
            break;
          case 'A':
            if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
              //unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);		
              move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);

              \Drupal::messenger()->addError(t("File !filename already exists hence overwirtten the exisitng file ", [
                '!filename' => $_FILES['files']['name'][$file_form_name]
                ]));
            } //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
					/* uploading file */
            else {
              if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
                /* for uploaded files making an entry in the database */
                $query_ab_f = "SELECT * FROM om_flowsheet_submitted_abstracts_file WHERE proposal_id = :proposal_id AND filetype = 
				:filetype";
                $args_ab_f = [
                  ":proposal_id" => $proposal_id,
                  ":filetype" => $file_type,
                ];
                $query_ab_f_result = \Drupal::database()->query($query_ab_f, $args_ab_f)->fetchObject();
                if (!$query_ab_f_result) {
                  $query = "INSERT INTO {om_flowsheet_submitted_abstracts_file} (submitted_abstract_id, proposal_id, uid, approvar_uid, filename, filepath, filemime, filesize, filetype, timestamp)
          VALUES (:submitted_abstract_id, :proposal_id, :uid, :approvar_uid, :filename, :filepath, :filemime, :filesize, :filetype, :timestamp)";
                  $args = [
                    ":submitted_abstract_id" => $submitted_abstract_id,
                    ":proposal_id" => $proposal_id,
                    ":uid" => $user->uid,
                    ":approvar_uid" => 0,
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":filetype" => $file_type,
                    ":timestamp" => time(),
                  ];
                  \Drupal::database()->query($query, $args);
                  \Drupal::messenger()->addStatus($file_name . ' uploaded successfully.');
                } //!$query_ab_f_result
                else {
                  unlink($root_path . $dest_path . $query_ab_f_result->filename);
                  $query = "UPDATE {om_flowsheet_submitted_abstracts_file} SET filename = :filename, filepath=:filepath, filemime=:filemime, filesize=:filesize, timestamp=:timestamp WHERE proposal_id = :proposal_id AND filetype = :filetype";
                  $args = [
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $file_path . $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":timestamp" => time(),
                    ":proposal_id" => $proposal_id,
                    ":filetype" => $file_type,
                  ];
                  \Drupal::database()->query($query, $args);
                  \Drupal::messenger()->addStatus($file_name . ' file updated successfully.');
                }
              } //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
              else {
                \Drupal::messenger()->addError('Error uploading file : ' . $dest_path . $file_name);
              }
            }
            break;
          /*case 'P':
					if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]))
					{
						//unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);		
						move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);

						drupal_set_message(t("File !filename already exists hence overwirtten the exisitng file ", array(
							'!filename' => $_FILES['files']['name'][$file_form_name]
						)), 'error');
					} //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
					/* uploading file 
					else if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name]))
					{
						/* for uploaded files making an entry in the database 
						$query_ab_f = "SELECT * FROM om_flowsheet_submitted_abstracts_file WHERE proposal_id = :proposal_id AND filetype = 
				:filetype";
						$args_ab_f = array(
							":proposal_id" => $proposal_id,
							":filetype" => $file_type
						);
						$query_ab_f_result = db_query($query_ab_f, $args_ab_f)->fetchObject();
						if (!$query_ab_f_result)
						{
							$query = "INSERT INTO {om_flowsheet_submitted_abstracts_file} (submitted_abstract_id, proposal_id, uid, approvar_uid, filename, filepath, filemime, filesize, filetype, timestamp)
          VALUES (:submitted_abstract_id, :proposal_id, :uid, :approvar_uid, :filename, :filepath, :filemime, :filesize, :filetype, :timestamp)";
							$args = array(
								":submitted_abstract_id" => $submitted_abstract_id,
								":proposal_id" => $proposal_id,
								":uid" => $user->uid,
								":approvar_uid" => 0,
								":filename" => $_FILES['files']['name'][$file_form_name],
								":filepath" => $_FILES['files']['name'][$file_form_name],
								":filemime" => mime_content_type($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]),
								":filesize" => $_FILES['files']['size'][$file_form_name],
								":filetype" => $file_type,
								":timestamp" => time()
							);
							db_query($query, $args);
							drupal_set_message($file_name . ' uploaded successfully.', 'status');
						} //!$query_ab_f_result
						else
						{
							unlink($root_path . $dest_path . $query_ab_f_result->filename);
							$query = "UPDATE {om_flowsheet_submitted_abstracts_file} SET filename = :filename, filepath=:filepath, filemime=:filemime, filesize=:filesize, timestamp=:timestamp WHERE proposal_id = :proposal_id AND filetype = :filetype";
							$args = array(
								":filename" => $_FILES['files']['name'][$file_form_name],
								":filepath" => $file_path . $_FILES['files']['name'][$file_form_name],
								":filemime" => mime_content_type($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]),
								":filesize" => $_FILES['files']['size'][$file_form_name],
								":timestamp" => time(),
								":proposal_id" => $proposal_id,
								":filetype" => $file_type
							);
							db_query($query, $args);
							drupal_set_message($file_name . ' file updated successfully.', 'status');
						}
					} //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
					else
					{
						drupal_set_message('Error uploading file : ' . $dest_path . $file_name, 'error');
					}
					break; */
        } //$file_type
      } //$file_name
    } //$_FILES['files']['name'] as $file_form_name => $file_name
	/* sending email */
    $email_to = $user->mail;
    $from = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_from_email');
    $bcc = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_emails');
    $cc = \Drupal::config('om_flowsheet.settings')->get('om_flowsheet_cc_emails');
    $params['abstract_uploaded']['proposal_id'] = $proposal_id;
    $params['abstract_uploaded']['submitted_abstract_id'] = $submitted_abstract_id;
    $params['abstract_uploaded']['user_id'] = $user->uid;
    $params['abstract_uploaded']['headers'] = [
      'From' => $from,
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
      'Cc' => $cc,
      'Bcc' => $bcc,
    ];
    if (!drupal_mail('om_flowsheet', 'abstract_uploaded', $email_to, language_default(), $params, $from, TRUE)) {
      \Drupal::messenger()->addError('Error sending email message.');
    }
    drupal_goto('chemical/flowsheeting-project/abstract-code');
  }

}
?>
