<?php /**
 * @file
 * Contains \Drupal\om_flowsheet\Controller\DefaultController.
 */

namespace Drupal\om_flowsheet\Controller;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Service;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\Markup;


/**
 * Default controller for the om_flowsheet module.
 */
class DefaultController extends ControllerBase {

  public function om_flowsheet_proposal_pending() {
    /* get pending proposals to be approved */
    $pending_rows = [];
    //$pending_q = db_query("SELECT * FROM {om_flowsheet_proposal} WHERE approval_status = 0 ORDER BY id DESC");
    $query = \Drupal::database()->select('om_flowsheet_proposal');
    $query->fields('om_flowsheet_proposal');
    $query->condition('approval_status', 0);
    $query->orderBy('id', 'DESC');
    $pending_q = $query->execute();
    while ($pending_data = $pending_q->fetchObject()) {
     $approval_url = Link::fromTextAndUrl(
  $this->t('Approve'),
  Url::fromUri('internal:/chemical/flowsheeting-project/manage-proposal/approve/' . $pending_data->id)
)->toString();
      $edit_url =  Link::fromTextAndUrl('Edit', Url::fromRoute('om_flowsheet.proposal_edit_form',['id'=>$pending_data->id]))->toString();
      $mainLink = t('@linkApprove | @linkReject', array('@linkApprove' => $approval_url, '@linkReject' => $edit_url));
      $pending_rows[$pending_data->id] = [
        date('d-m-Y', $pending_data->creation_date),
        Link::fromTextAndUrl($pending_data->contributor_name, Url::fromRoute('entity.user.canonical', ['user' => $pending_data->uid])),
        $pending_data->project_title,
        $mainLink
      ];

    } //$pending_data = $pending_q->fetchObject()
	/* check if there are any pending proposals */
    if (!$pending_rows) {
      \Drupal::messenger()->addStatus(t('There are no pending proposals.'));
      return '';
    } //!$pending_rows
    $pending_header = [
      'Date of Submission',
      'Student Name',
      'Title of the Flowsheet Project',
      'Action',
    ];
    $output =  [
      '#type' => 'table',
      '#header' => $pending_header,
      '#rows' => $pending_rows,
      '#empty' => 'no rows found',
    ];
    return $output;
  }

  public function om_flowsheet_proposal_all() {
    /* get pending proposals to be approved */
    $proposal_rows = [];
    //$proposal_q = db_query("SELECT * FROM {om_flowsheet_proposal} ORDER BY id DESC");
    $query = \Drupal::database()->select('om_flowsheet_proposal');
    $query->fields('om_flowsheet_proposal');
    $query->orderBy('id', 'DESC');
    $proposal_q = $query->execute();
    while ($proposal_data = $proposal_q->fetchObject()) {
      $approval_status = '';
      switch ($proposal_data->approval_status) {
        case 0:
          $approval_status = 'Pending';
          break;
        case 1:
          $approval_status = 'Approved';
          break;
        case 2:
          $approval_status = 'Dis-approved';
          break;
        case 3:
          $approval_status = 'Completed';
          break;
        default:
          $approval_status = 'Unknown';
          break;
      } //$proposal_data->approval_status
      if ($proposal_data->actual_completion_date == 0) {
        $actual_completion_date = "Not Completed";
      } //$proposal_data->actual_completion_date == 0
      else {
        $actual_completion_date = date('d-m-Y', $proposal_data->actual_completion_date);
      }
      //$approval_url = '';
      $approval_url = Link::fromTextAndUrl(
  $this->t('Status'),
  Url::fromUri('internal:/chemical/flowsheeting-project/manage-proposal/status/' . $proposal_data->id)
)->toString();
      //$approval_url = Link::fromTextAndUrl('Status', Url::fromRoute('om_flowsheet.proposal_status_form',['id'=>$proposal_data->id]))->toString();
      $edit_url =  Link::fromTextAndUrl('Edit', Url::fromRoute('om_flowsheet.proposal_edit_form',['id'=>$proposal_data->id]))->toString();
      $mainLink = t('@linkApprove | @linkReject', array('@linkApprove' => $approval_url, '@linkReject' => $edit_url));
      $proposal_rows[$proposal_data->id] = [
        date('d-m-Y', $proposal_data->creation_date),
        Link::fromTextAndUrl($proposal_data->contributor_name, Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid])),
        $proposal_data->project_title,
        $actual_completion_date,
        $approval_status,
        $mainLink
      ];

    } //$proposal_data = $proposal_q->fetchObject()
	/* check if there are any pending proposals */
    if (!$proposal_rows) {
      \Drupal::messenger()->addStatus(t('There are no proposals.'));
      return '';
    } //!$proposal_rows
    $proposal_header = [
      'Date of Submission',
      'Student Name',
      'Title of the Lab',
      'Date of Completion',
      'Status',
      'Action',
    ];
    $output =  [
      '#type' => 'table',
      '#header' => $proposal_header,
      '#rows' => $proposal_rows,
      '#empty' => 'no rows found',
    ];
    return $output;
  }

  public function om_flowsheet_abstract() {
    $user = \Drupal::currentUser();
    $return_html = "";
    $proposal_data = om_flowsheet_get_proposal();
    if (!$proposal_data) {
      drupal_goto('chemical');
      return;
    } //!$proposal_data
    //$return_html .= l('Upload abstract', 'flowsheeting-project/abstract-code/upload') . '<br />';
	/* get experiment list */
    $query = \Drupal::database()->select('om_flowsheet_submitted_abstracts');
    $query->fields('om_flowsheet_submitted_abstracts');
    $query->condition('proposal_id', $proposal_data->id);
    $abstracts_q = $query->execute()->fetchObject();
    if ($abstracts_q) {
      if ($abstracts_q->is_submitted == 1) {
        \Drupal::messenger()->addError(t('Your abstract is under review, you can not edit exisiting abstract without reviewer permission.'));
        //drupal_goto('flowsheeting-project/abstract-code');
        //return;
      } //$abstracts_q->is_submitted == 1
    } //$abstracts_q
    //var_dump($abstracts_q->library_used_id);die;
    $query = \Drupal::database()->select('om_flowsheet_library');
    $query->fields('om_flowsheet_library');
    $query->condition('id', $proposal_data->simulator_version_id);
    $result = $query->execute()->fetchObject();
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $simulator_version_used_name = l($result->simulator_version_name, $result->link);

    $query_pro = \Drupal::database()->select('om_flowsheet_proposal');
    $query_pro->fields('om_flowsheet_proposal');
    $query_pro->condition('id', $proposal_data->id);
    $abstracts_pro = $query_pro->execute()->fetchObject();
    $query_pdf = \Drupal::database()->select('om_flowsheet_submitted_abstracts_file');
    $query_pdf->fields('om_flowsheet_submitted_abstracts_file');
    $query_pdf->condition('proposal_id', $proposal_data->id);
    $query_pdf->condition('filetype', 'A');
    $abstracts_pdf = $query_pdf->execute()->fetchObject();
    if ($abstracts_pdf == TRUE) {
      if ($abstracts_pdf->filename != "NULL" || $abstracts_pdf->filename != "") {
        $abstract_filename = $abstracts_pdf->filename;
      } //$abstracts_pdf->filename != "NULL" || $abstracts_pdf->filename != ""
      else {
        $abstract_filename = "File not uploaded";
      }
    } //$abstracts_pdf == TRUE
    else {
      $abstract_filename = "File not uploaded";
    }
    /* Code to upload the simulator package(used earlier)
	$query_sp = db_select('om_flowsheet_submitted_abstracts_file');
	$query_sp->fields('om_flowsheet_submitted_abstracts_file');
	$query_sp->condition('proposal_id', $proposal_data->id);
	$query_sp->condition('filetype', 'P');
	$abstracts_sp = $query_sp->execute()->fetchObject();
	if ($abstracts_sp == TRUE)
	{
		if ($abstracts_sp->filename != "NULL" || $abstracts_sp->filename != "")
		{
			$sp_filename = $abstracts_sp->filename;
		} //$abstracts_pdf->filename != "NULL" || $abstracts_pdf->filename != ""
		else
		{
			$sp_filename = "File not uploaded";
		}
	} //$abstracts_pdf == TRUE
	else
	{
		$sp_filename = "File not uploaded";
	}*/
    $query_process = \Drupal::database()->select('om_flowsheet_submitted_abstracts_file');
    $query_process->fields('om_flowsheet_submitted_abstracts_file');
    $query_process->condition('proposal_id', $proposal_data->id);
    $query_process->condition('filetype', 'S');
    $abstracts_query_process = $query_process->execute()->fetchObject();
    if ($abstracts_query_process == TRUE) {
      if ($abstracts_query_process->filename != "NULL" || $abstracts_query_process->filename != "") {
        $abstracts_query_process_filename = $abstracts_query_process->filename;
      } //$abstracts_query_process->filename != "NULL" || $abstracts_query_process->filename != ""
      else {
        $abstracts_query_process_filename = "File not uploaded";
      }
      if ($abstracts_q->is_submitted == '') {
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $url = l('Upload abstract', 'chemical/flowsheeting-project/abstract-code/upload');

      } //$abstracts_q->is_submitted == ''
      else {
        if ($abstracts_q->is_submitted == 1) {
          $url = "";
        } //$abstracts_q->is_submitted == 1
        else {
          if ($abstracts_q->is_submitted == 0) {
            // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $url = l('Edit abstract', 'chemical/flowsheeting-project/abstract-code/upload');

          }
        }
      } //$abstracts_q->is_submitted == 0
      if ($abstracts_q->unit_operations_used_in_om == '') {
        $unit_operations_used_in_om = "Not entered";
      } //$abstracts_q->unit_operations_used_in_om == ''
      else {
        $unit_operations_used_in_om = $abstracts_q->unit_operations_used_in_om;
      }
      if ($abstracts_q->thermodynamic_packages_used == '') {
        $thermodynamic_packages_used = "Not entered";
      } //$abstracts_q->thermodynamic_packages_used == ''
      else {
        $thermodynamic_packages_used = $abstracts_q->thermodynamic_packages_used;
      }

    } //$abstracts_query_process == TRUE
    else {
      // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $url = l('Upload abstract', 'chemical/flowsheeting-project/abstract-code/upload');

      $unit_operations_used_in_om = "Not entered";
      $thermodynamic_packages_used = "Not entered";
      $abstracts_query_process_filename = "File not uploaded";
      //$sp_filename = "File not uploaded";
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

    $uploaded_user_defined_compound_filepath = basename($proposal_data->user_defined_compound_filepath) ? basename($proposal_data->user_defined_compound_filepath) : "Not uploaded";
    $return_html .= '<strong>Proposer Name:</strong><br />' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '<br /><br />';
    $return_html .= '<strong>Title of the Flowsheet Project:</strong><br />' . $proposal_data->project_title . '<br /><br />';
    $return_html .= '<strong>OpenModelica version:</strong><br />' . $proposal_data->version . '<br /><br />';
    $return_html .= '<strong>Unit Operations used in OpenModelica:</strong><br />' . $unit_operations_used_in_om . '<br /><br />';
    $return_html .= '<strong>Thermodynamic Packages Used:</strong><br />' . $thermodynamic_packages_used . '<br /><br />';
    $return_html .= '<strong>Name of compound for which process development is carried out:</strong><br />' . $prodata . '<br />';
    $return_html .= '<strong>Uploaded an abstract (brief outline) of the project:</strong><br />' . $abstract_filename . '<br /><br />';
    $return_html .= '<strong>Upload the OpenModelica flowsheet for the developed process:</strong><br />' . $abstracts_query_process_filename . '<br /><br />';
    $return_html .= '<strong>Simulator version used for creating the flowsheet:</strong><br />' . $simulator_version_used_name . '<br /><br />';
    $return_html .= $url . '<br />';
    return $return_html;
  }

  public function om_flowsheet_download_abstract_file() {
    $proposal_id = \Drupal::routeMatch()->getParameter('proposal_id');
    $root_path = om_flowsheet_document_path();
    $query = \Drupal::database()->select('om_flowsheet_submitted_abstracts_file');
    $query->fields('om_flowsheet_submitted_abstracts_file');
    $query->condition('proposal_id', $proposal_id);
    $query->condition('filetype', 'A');
    $result = $query->execute();
    $om_pssp_project_files = $result->fetchObject();
    //var_dump($om_pssp_project_files);die;
    $query1 = \Drupal::database()->select('om_flowsheet_proposal');
    $query1->fields('om_flowsheet_proposal');
    $query1->condition('id', $proposal_id);
    $result1 = $query1->execute();
    $om_pssp = $result1->fetchObject();
    $directory_name = $om_pssp->directory_name . '/';
    $samplecodename = $om_pssp_project_files->filename;
    ob_clean();
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Type: application/pdf");
    header('Content-disposition: attachment; filename="' . $samplecodename . '"');
    header("Content-Length: " . filesize($root_path . $directory_name . $samplecodename));
    header("Content-Transfer-Encoding: binary");
    header("Expires: 0");
    header("Pragma: no-cache");
    readfile($root_path . $directory_name . $samplecodename);
    ob_end_flush();
    ob_clean();
  }

  public function om_flowsheet_download_full_project() {
    $user = \Drupal::currentUser();
    $flowsheet_id = \Drupal::routeMatch()->getParameter('proposal_id');
    //var_dump($flowsheet_id);die;
    $root_path = om_flowsheet_document_path();
    $query = \Drupal::database()->select('om_flowsheet_proposal');
    $query->fields('om_flowsheet_proposal');
    $query->condition('id', $flowsheet_id);
    $flowsheet_q = $query->execute();
    $flowsheet_data = $flowsheet_q->fetchObject();
    $FLOWSHEET_PATH = $flowsheet_data->directory_name . '/';
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    //var_dump($zip_filename);die;
	/* creating zip archive on the server */
    $zip = new \ZipArchive();
    $zip->open($zip_filename, \ZipArchive::CREATE);
    $query = \Drupal::database()->select('om_flowsheet_proposal');
    $query->fields('om_flowsheet_proposal');
    $query->condition('id', $flowsheet_id);
    $flowsheet_udc_q = $query->execute();
    $query = \Drupal::database()->select('om_flowsheet_submitted_abstracts_file');
    $query->fields('om_flowsheet_submitted_abstracts_file');
    $query->condition('proposal_id', $flowsheet_id);
    $flowsheet_f_q = $query->execute();
    while ($flowsheet_f_row = $flowsheet_f_q->fetchObject()) {
      $zip->addFile($root_path . $FLOWSHEET_PATH . '/' . $flowsheet_f_row->filepath, $FLOWSHEET_PATH . str_replace(' ', '_', basename($flowsheet_f_row->filename)));
    } //$flowsheet_f_row = $flowsheet_f_q->fetchObject()
    $zip_file_count = $zip->numFiles;
    //var_dump($zip_file_count);die;
    $zip->close();
    //var_dump(filesize($zip_filename));die;
    if ($zip_file_count > 0) {
      
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename="' . str_replace(' ', '_', $flowsheet_data->project_title) . '.zip"');
        header('Content-Length: ' . filesize($zip_filename));
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        header('Pragma: no-cache');
        ob_end_flush();
        flush();
        readfile($zip_filename);
        unlink($zip_filename);
    } //$zip_file_count > 0
    else {
      \Drupal::messenger()->addError("There are no flowsheet project in this proposal to download");
      return new RedirectResponse('/chemical/flowsheeting-project/om-flowsheet-run/' . $proposal_id);

      //drupal_goto('chemical/flowsheeting-project/full-download/project');
    }
  }

  public function om_flowsheet_completed_proposals_all() {
    $output = "";
    $count_query = \Drupal::database()->select('om_flowsheet_proposal', 't')
  ->condition('approval_status', '3')
  ->countQuery();

$i = $count_query->execute()->fetchField(); 
    $query = \Drupal::database()->select('om_flowsheet_proposal');
    $query->fields('om_flowsheet_proposal');
    $query->condition('approval_status', 3);
    $query->orderBy('actual_completion_date', 'DESC');
    $result = $query->execute();
    /*if ($i == 0) {
      //$output .= "Work has been completed for the following flow sheets. We welcome your contributions. For more details, please visit " . \Drupal\Core\Link::fromTextAndUrl("https://om.fossee.in/chemical/flowsheeting-project", \Drupal\Core\Url::fromUri("https://om.fossee.in/chemical/flowsheeting-project")) . "<hr>";

    } //$result->rowCount() == 0
    else {*/
      //$output .= "Work has been completed for the following flow sheets. We welcome your contributions. For more details, please visit " . \Drupal\Core\Link::fromTextAndUrl("https://om.fossee.in/chemical/flowsheeting-project", \Drupal\Core\Url::fromUri("https://om.fossee.in/chemical/flowsheeting-project")) . "<hr>";
      $preference_rows = [];
      
      while ($row = $result->fetchObject()) {
        //var_dump($row);die;
        $completion_date = date("Y", $row->actual_completion_date);
        $url = Url::fromUri('internal:/chemical/flowsheeting-project/om-flowsheet-run/' . $row->id, [
  'attributes' => [
    'title' => 'This is a zip file containing a pdf (abstract) and a mo file which is the OpenModelica flowsheet which is to be viewed by right-clicking on the file and opening with OpenModelica.',
  ],
]);
        $link = Link::fromTextAndUrl($row->project_title, $url)->toString();

        $preference_rows[] = array(
        				$i,
        				$link,
        				$row->contributor_name,
        				$row->university,
        				$completion_date
        			);

        $i--;
      } //$row = $result->fetchObject()
      $preference_header = [
        'No',
        'Flowsheet Project',
        'Contributor Name',
        'University / Institute',
        'Year of Completion',
      ];
      $output =  [
      '#type' => 'table',
      '#header' => $preference_header,
      '#rows' => $preference_rows,
      //'#empty' => 'no rows found',
    ];

   //}
    return $output;
  }

  public function om_flowsheet_progress_all() {
    $page_content = "";
    $count_query = \Drupal::database()->select('om_flowsheet_proposal', 't')
  ->condition('approval_status', '1')
  ->countQuery();

$i = $count_query->execute()->fetchField(); 
    $query = \Drupal::database()->select('om_flowsheet_proposal');
    $query->fields('om_flowsheet_proposal');
    $query->condition('approval_status', 1);
    $query->condition('is_completed', 0);
    $query->orderBy('approval_date', 'DESC');
    $result = $query->execute();
    if ($i == 0) {
      $page_content .= "Work is in progress for the following flowsheets under Flowsheeting Project<hr>";
    } //$result->rowCount() == 0
    else {
      $page_content .= "Work is in progress for the following flowsheets under Flowsheeting Project<hr>";
      ;
      $preference_rows = [];
      //$i = $result->rowCount();
      while ($row = $result->fetchObject()) {
        $approval_date = date("Y", $row->approval_date);
        $preference_rows[] = [
          $i,
          $row->project_title,
          $row->contributor_name,
          $row->university,
          $approval_date,
        ];
        $i--;
      } //$row = $result->fetchObject()
      $preference_header = [
        'No',
        'Flowsheet Project',
        'Contributor Name',
        'University / Institute',
        'Year',
      ];
      
$page_content =  [
      '#type' => 'table',
      '#header' => $preference_header,
      '#rows' => $preference_rows,
      //'#empty' => 'no rows found',
    ];
    }
    return $page_content;
  }

  public function om_flowsheet_download_user_defined_compound() {
    $proposal_id = arg(3);
    $root_path = om_flowsheet_document_path();
    $query = \Drupal::database()->select('om_flowsheet_proposal');
    $query->fields('om_flowsheet_proposal');
    $query->condition('id', $proposal_id);
    $query->range(0, 1);
    $result = $query->execute();
    $om_flowsheet_user_compund_data = $result->fetchObject();
    $samplecodename = substr($om_flowsheet_user_compund_data->user_defined_compound_filepath, strrpos($om_flowsheet_user_compund_data->user_defined_compound_filepath, '/') + 1);
    header('Content-Type: txt/zip');
    header('Content-disposition: attachment; filename="' . $samplecodename . '"');
    header('Content-Length: ' . filesize($root_path . $om_flowsheet_user_compund_data->directory_name . '/' . $om_flowsheet_user_compund_data->user_defined_compound_filepath));
    ob_clean();
    readfile($root_path . $om_flowsheet_user_compund_data->directory_name . '/' . $om_flowsheet_user_compund_data->user_defined_compound_filepath);
  }

  public function _list_flowsheet_certificates() {
    $user = \Drupal::currentUser();
    $query_id = \Drupal::database()->query("SELECT id FROM om_flowsheet_proposal WHERE approval_status=3 AND uid= :uid", [
      ':uid' => $user->id()
      ]);
    $exist_id = $query_id->fetchObject();
    //var_dump($exist_id);die;
    //var_dump($exist_id->id);die;
    //if ($exist_id) {
          $search_rows = [];
          
          $query3 = \Drupal::database()->query("SELECT id,project_title,contributor_name FROM om_flowsheet_proposal WHERE approval_status=3 AND uid= :uid", [
            ':uid' => $user->id()
            ]);
          while ($search_data3 = $query3->fetchObject()) {
              $url = Url::fromUri('internal:/chemical/flowsheeting-project/certificates-custom/pdf/' . $search_data3->id);
$link = Link::fromTextAndUrl(t('Download Certificate'), $url)->toString();
            $search_rows[] = array(
  						$search_data3->project_title,
  						$search_data3->contributor_name,
  						$link
					  );
          } //$search_data3 = $query3->fetchObject()
            $search_header = [
              'Project Title',
              'Contributor Name',
              'Download Certificates',
            ];
            
            $output= [
              '#type' => 'table',
            	'#header' => $search_header,
            	'#rows' => $search_rows,
              '#empty' => 'No Certificates Found'
            ];
          return $output;
  }

  /*public function verify_certificates($qr_code = 0) {
    $qr_code = arg(4);
    $page_content = "";
    if ($qr_code) {
      $page_content = verify_qrcode_fromdb($qr_code);
    } //$qr_code
    else {
      $verify_certificates_form = drupal_get_form("verify_certificates_form");
      $page_content = drupal_render($verify_certificates_form);
    }
    return $page_content;
  }*/

}
