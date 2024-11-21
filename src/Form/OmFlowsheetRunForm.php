<?php
namespace Drupal\om_flowsheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Database;

class OmFlowsheetRunForm extends FormBase {
  
  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'om_flowsheet_run_form';
  }

  /**
   * Build the form with AJAX support.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $url_flowsheet_id = \Drupal::routeMatch()->getParameter('proposal_id');
    $options = $this->getFlowsheetList();
    if (!$url_flowsheet_id) {
      $selected = !$form_state->getValue(['flowsheet']) ? $form_state->getValue(['flowsheet']) : key($options_first);
    } //!$url_flowsheet_id
    elseif ($url_flowsheet_id == '') {
      $selected = 0;
    } //$url_flowsheet_id == ''
    else {
      $selected = $url_flowsheet_id;
    }

    $form['flowsheet'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the flowsheet'),
      '#options' => $options,
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::ajaxProjectDetailsCallback',
        'wrapper' => 'ajax_flowsheet_details',
      ],
    ];

    $form['flowsheet_details'] = [
      '#type' => 'markup',
      '#markup' => '<div id="ajax_flowsheet_details"> ' . $this->getFlowsheetDetails($url_flowsheet_id) . '</div>',
    ];

   $abstract_url = Url::fromUri('internal:/chemical/flowsheeting-project/download/abstract-file/' . $url_flowsheet_id);
$flowsheet_url = Url::fromUri('internal:/chemical/flowsheeting-project/full-download/project/' . $url_flowsheet_id, [
  'attributes' => [
    'title' => 'This is a zip file containing a pdf (abstract) and a mo file which is the OpenModelica flowsheet which is to be viewed by right clicking on the file and opening with OpenModelica.',
  ],
]);

// Create the links using Link::fromTextAndUrl()
$abstract_link = Link::fromTextAndUrl('Download Abstract', $abstract_url)->toString();
$flowsheet_link = Link::fromTextAndUrl('Download Flowsheet', $flowsheet_url)->toString();
      $form['selected_flowsheet'] = array(
            '#type' => 'item',
            '#markup' => '<div id="ajax_selected_flowsheet">' . $abstract_link . '<br>' . $flowsheet_link . '<hr><p style="color:red;">Note: This flowsheet is compatible with the simulator version mentioned above. To simulate the flowsheet in OpenModelica, it must be opened with the above Simulator version.<br>To download the Simulator, click on the Simulator version mentioned above.</p></div>'
          );

    return $form;
  }

  /**
   * AJAX callback function.
   */
  public function ajaxProjectDetailsCallback(array &$form, FormStateInterface $form_state) {
    $selected_flowsheet = $form_state->getValue('flowsheet');
    $response = new AjaxResponse();

    if ($selected_flowsheet) {
      $details = $this->getFlowsheetDetails($selected_flowsheet);
      $response->addCommand(new HtmlCommand('#ajax_flowsheet_details', $details));
      $abstract_link = Link::fromTextAndUrl('Download Abstract', Url::fromUri('internal:/chemical/flowsheeting-project/download/abstract-file/' . $selected_flowsheet))->toString();
      $flowsheet_link = Link::fromTextAndUrl('Download Flowsheet', Url::fromUri('internal:/chemical/flowsheeting-project/full-download/project/' . $selected_flowsheet, [
        'attributes' => [
          'title' => 'This is a zip file containing a pdf (abstract) and a dwxml/dwxmz file which is the om flow sheet to be viewed by right-clicking on the file and opening with om.',
        ],
      ]))->toString();

      // Combine links with additional information
      $downloads_markup = $abstract_link . '<br>' . $flowsheet_link .
        '<hr><p style="color:red;">Note: This flowsheet is compatible with the simulator version mentioned above. To simulate the flowsheet in OpenModelica, it must be opened with the above Simulator version.<br>To download the Simulator, click on the Simulator version mentioned above.</p>';

      // Add commands to update specific areas in the page
      $response->addCommand(new HtmlCommand('#ajax_selected_flowsheet', $downloads_markup));
    } else {
      // Clear out details if no valid selection.
      $response->addCommand(new HtmlCommand('#ajax_flowsheet_details', ''));
      $response->addCommand(new HtmlCommand('#ajax_selected_flowsheet', ''));
    }

    return $response;
  }

  /**
   * Retrieve list of flowsheets.
   */
  protected function getFlowsheetList() {
    $options = ['0' => $this->t('Please select...')];
    $query = $this->database->select('om_flowsheet_proposal', 'f')
      ->fields('f', ['id', 'project_title', 'name_title', 'contributor_name'])
      ->condition('approval_status', 3)
      ->orderBy('project_title', 'ASC')
      ->execute();

    foreach ($query as $record) {
      $options[$record->id] = "{$record->project_title} (Proposed by {$record->name_title} {$record->contributor_name})";
    }
    
    return $options;
  }

  /**
   * Fetch flowsheet details.
   */
  protected function getFlowsheetDetails($flowsheet_id) {
    // Get flowsheet and abstract details.
    $flowsheet = $this->database->select('om_flowsheet_proposal', 'f')
      ->fields('f')
      ->condition('id', $flowsheet_id)
      ->execute()
      ->fetchObject();
$simulator_query = Database::getConnection()->select('om_flowsheet_library', 'l');
  $simulator_query->fields('l');
  $simulator_query->condition('l.id', $flowsheet->simulator_version_id);
  $result = $simulator_query->execute()->fetchObject();
    if ($flowsheet) {
      $link = Link::fromTextAndUrl($flowsheet->project_title, Url::fromUri("internal:/chemical/flowsheeting-project/download/abstract-file/{$flowsheet_id}"))->toString();
      //$flowsheet_link = Link::fromTextAndUrl($flowsheet->simulator_version_id, Url::fromUri("internal:/chemical/flowsheeting-project/full-download/project/{$flowsheet_id}"))->toString();
      $simulator_version_used = Link::fromTextAndUrl(
  $result->simulator_version_name,
  Url::fromUri($result->link)
)->toString();

      return "<div>
        <strong>{$this->t('About the Flowsheet')}</strong>
        <ul>
          <li><strong>{$this->t('Proposer Name')}:</strong> {$flowsheet->name_title} {$flowsheet->contributor_name}</li>
          <li><strong>{$this->t('Title of the Flowsheet')}:</strong> $link</li>
          <li><strong>{$this->t('Institution')}:</strong> {$flowsheet->university}</li>
          <li><strong>{$this->t('Version')}:</strong> {$flowsheet->version}</li>
          <li><strong>{$this->t('Reference')}:</strong> {$flowsheet->reference}</li>
          <li><strong>{$this->t('Simulator version used')}:</strong> {$simulator_version_used}</li>
        </ul>
      </div>";
    }
    return $this->t('Not found');
  }
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
}
}
?>