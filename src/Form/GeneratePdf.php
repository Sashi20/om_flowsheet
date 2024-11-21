<?php

/**
 * @file
 * Contains \Drupal\om_flowsheet\Form\GeneratePdf.
 */

namespace Drupal\om_flowsheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use FPDF;
use QRcode;

class GeneratePdf extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'generate_pdf';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $mpath = \Drupal::service('extension.list.module')->getPath('om_flowsheet');
    //$mpath = drupal_get_path('module', 'om_flowsheet');
    require($mpath . '/pdf/fpdf/fpdf.php');
    require($mpath . '/pdf/phpqrcode/qrlib.php');
    $user = \Drupal::currentUser();
    $x = $user->id();
    $proposal_id = \Drupal::routeMatch()->getParameter('proposal_id');
    $query3 = \Drupal::database()->query("SELECT * FROM om_flowsheet_proposal WHERE approval_status=3 AND uid= :uid AND id=:proposal_id", [
      ':uid' => $user->id(),
      ':proposal_id' => $proposal_id,
    ]);
    $data3 = $query3->fetchObject();
    if ($data3) {
      if ($data3->uid != $x) {
        \Drupal::messenger()->addError('Certificate is not available');
        return;
      }
    }
    $pdf = new FPDF('L', 'mm', 'Letter');
    if (!$pdf) {
      echo "Error!";
    } //!$pdf
    $pdf->AddPage();
    $image_bg = DRUPAL_ROOT . '/'. $mpath . "/pdf/images/bg_cert.png";
    //var_dump($image_bg);die;
    $pdf->Image($image_bg, 0, 0, $pdf->GetPageWidth(), $pdf->GetPageHeight());
    //$pdf->Rect(5, 5, 267, 207, 'D');
    $pdf->SetMargins(18, 1, 18);
    //$pdf->Line(7.0, 7.0, 270.0, 7.0);
    //$pdf->Line(7.0, 7.0, 7.0, 210.0);
    //$pdf->Line(270.0, 210.0, 270.0, 7.0);
    //$pdf->Line(7.0, 210.0, 270.0, 210.0);
    $path = \Drupal::service('extension.list.module')->getPath('om_flowsheet');
    //$image1 = $mpath . "/pdf/images/dwsim_logo.png";
    $pdf->Ln(30);
    //$pdf->Cell(200, 8, $pdf->Image($image1, 105, 15, 0, 28), 0, 1, 'C');
    //$pdf->Ln(20);

    //$pdf->SetTextColor(139, 69, 19);
    //$pdf->Cell(240, 8, 'Certificate of Participation', '0', 1, 'C');
    //$pdf->Ln(26);
    $pdf->SetFont('Times', '', 18);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(240, 10, 'This is to certify that', '0', '1', 'C');
    $pdf->Ln(0);
    //$pdf->SetFont('Times', 'I', 20);
    //$pdf->SetFont('Arial', 'BI', 25);
    $pdf->SetTextColor(37, 22, 247);
    $contributor_name = WordWrap($data3->contributor_name, 70);
    $pdf->MultiCell(240, 10, $data3->name_title . '. ' . $contributor_name, '0', 'C');
    //$pdf->Cell(320, 10, $data3->name_title . '. ' . $data3->contributor_name, '0', '1', 'C');
    $pdf->Ln(0);
    //$pdf->SetFont('Times', '', 18);
    if (strtolower($data3->branch) != "others") {
      $title = WordWrap($data3->project_title, 70);
      $university = WordWrap('from ' . $data3->university . ' has successfully completed an internship under', 80);
      $pdf->SetTextColor(0, 0, 0);
      //$pdf->Cell(240, 8, 'from ' . $data3->university . ' has successfully', '0', '1', 'C');
      $pdf->MultiCell(240, 10, $university, '0', 'C');
      $pdf->Ln(0);
      //$pdf->SetFont('Times','B',18);
      $pdf->Cell(240, 10, 'OpenModelica Flowsheeting Project. He/She has created a flowsheet titled', '0', '1', 'C');
      //$pdf->Ln(0);
      //$pdf->Cell(240, 10, 'He/She has created a flowsheet titled', '0', '1', 'C');
      $pdf->SetTextColor(37, 22, 247);
      //$pdf->SetFont('Times', 'I', 20);
      $pdf->MultiCell(240, 8, $title, '0', 'C');
      $pdf->SetTextColor(0, 0, 0);
      //$pdf->SetFont('Times', '', 18);
      $pdf->Cell(240, 10, 'using OpenModelica. The work done is available at', '0', '1', 'C');
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Ln(2);
      //$pdf->Cell(320, 8, 'The work done is available at', '0', '1', 'C');
      //$pdf->Cell(320, 4, '', '0', '1', 'C');
      $pdf->SetX(75);
      //$pdf->SetFont('Times', 'I', 'U');
      $pdf->SetTextColor(37, 22, 247);
      $pdf->write(0, 'https://om.fossee.in/chemical/flowsheeting-project', 'https://om.fossee.in/chemical/flowsheeting-project');
      $pdf->Ln(0);
      //$pdf->SetFont('Times', 'I', 16);
      //$pdf->Cell(320, 8, 'under Case Study Project', '0', '1', 'C');
      //$pdf->Ln(0);
      //$pdf->SetFont('Helvetica', '', 18);
      //$pdf->Cell(0, 0, ' Case Study Project', '0', '0', 'C');
      //$pdf->Cell(240, 4, '', '0', '1', 'C');
      //$pdf->SetX(120);
      //$pdf->SetFont('', 'U');
      //$pdf->SetTextColor(139, 69, 19);
      //$pdf->write(0, 'http://CFD.fossee.in/', 'http://CFD.fossee.in/');
      //$pdf->Ln(0);
      //$pdf->Cell(240, 8, 'Book: ' . $data2->book . ', Author: ' . $data2->author . '.', '0', '1', 'C');
      //$pdf->MultiCell(240, 8, 'Book: ' . $data2->book . ', Author: ' . $data2->author . '.', '0','C');
      $pdf->Ln(0);
    } //strtolower($data3->branch) != "others"
    else {
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(240, 8, 'from ' . $data3->university . ' college', '0', '1', 'C');
      $pdf->Ln(0);
      $pdf->Cell(240, 8, 'has successfully completed the case study of', '0', '1', 'C');
      $pdf->Ln(0);
      $pdf->SetTextColor(139, 69, 19);
      $pdf->Cell(320, 12, $data3->project_title, '0', '1', 'C');
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Ln(0);
      //$pdf->SetFont('Times', '', 16);
      $pdf->Cell(320, 8, ' under Case Study Project', '0', '1', 'C');
      //$pdf->Cell(240, 8, 'He/she has coded ' . $number_of_example . ' solved examples using DWSIM from the', '0', '1', 'C');
      //$pdf->Ln(0);
      //$pdf->Cell(240, 8, 'Book: ' . $data2->book . ', Author: ' . $data2->author . '.', '0', '1', 'C');
      //$pdf->Ln(0);
    }
    $proposal_get_id = 0;
    $UniqueString = "";
    $tempDir = DRUPAL_ROOT . '/'. $path . "/pdf/temp_prcode/";
    $query = \Drupal::database()->select('om_flowsheet_qr_code');
    $query->fields('om_flowsheet_qr_code');
    $query->condition('proposal_id', $proposal_id);
    $result = $query->execute();
    $data = $result->fetchObject();
    $DBString = $data->qr_code;
    $proposal_get_id = $data->proposal_id;
    if ($DBString == "" || $DBString == "null") {
      $UniqueString = generateRandomString();
      $query = "
				INSERT INTO om_flowsheet_qr_code
				(proposal_id,qr_code)
				VALUES
				(:proposal_id,:qr_code)
				";
      $args = [
        ":proposal_id" => $proposal_id,
        ":qr_code" => $UniqueString,
      ];
      $result = \Drupal::database()->query($query, $args, $query);
    } //$DBString == "" || $DBString == "null"
    else {
      $UniqueString = $DBString;
    }
    $codeContents = "https://om.fossee.in/chemical/flowsheeting-project/certificates/verify/" . $UniqueString;
    $fileName = 'generated_qrcode.png';
    $pngAbsoluteFilePath = $tempDir . $fileName;
    $urlRelativeFilePath = $path . "/pdf/temp_prcode/" . $fileName;
    QRcode::png($codeContents, $pngAbsoluteFilePath);
    $pdf->SetY(80);
    $pdf->SetX(300);
    $pdf->Ln(30);
    //$sign = $path . "/pdf/images/sign.png";
    //$pdf->Image($sign, $pdf->GetX()+70, $pdf->GetY() + 40, 80, 0);
    $pdf->Image($pngAbsoluteFilePath, $pdf->GetX() + 200, $pdf->GetY() + 20, 30, 0);
    //$pdf->Cell(240, 8, 'Prof. Kannan M. Moudgalya', 0, 1, 'R');
    //$pdf->SetX(199);
    //$pdf->SetFont('Arial', '', 10);
    //$pdf->Cell(0, 7, 'Co - Principal Investigator - FOSSEE', 0, 1, 'L');
    //$pdf->SetX(190);
    //$pdf->Cell(0, 7, ' Dept. of Chemical Engineering, IIT Bombay.', 0, 1, 'L');
    //$pdf->SetX(29);
    //$pdf->SetFont('Times', 'I', 15);
    //$pdf->SetY(-58);
    $pdf->Ln(50);
    $pdf->SetX(48);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(193, 8, $UniqueString, '0', '1', 'R');
    //$pdf->SetX(29);
    //$pdf->SetY(-50);
    //$image4 = $path . "/pdf/images/bottom_line.png";
    //$pdf->Image($image4, $pdf->GetX(), $pdf->GetY(), 20, 0);
    //$pdf->SetY(-50);
    //$pdf->SetX(80);
    //$image3 = $path . "/pdf/images/iitb.png";
    //$image2 = $path . "/pdf/images/fossee.png"; 

    //$pdf->Ln(8);
    //$pdf->Image($image2, $pdf->GetX() +15, $pdf->GetY() + 7, 40, 0);
    //$pdf->Ln(6);
    $pdf->SetY(150);
    $pdf->SetX(800);
    //$pdf->Ln(2);

    //$pdf->Image($image3, $pdf->GetX() + 200, $pdf->GetY() -3, 15, 0);
    //$pdf->Image($image4, $pdf->GetX() +50, $pdf->GetY() + 28, 150, 0);
    //$pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(0, 0, 0);
    $filename = str_replace(' ', '-', $data3->contributor_name) . '-OpenModelica-Flowsheeting-Certificate.pdf';
    $file = $path . '/pdf/temp_certificate/' . $proposal_id . '_' . $filename;
    $pdf->Output($file, 'F');
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=" . $filename);
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    header("Content-Description: File Transfer");
    header("Content-Length: " . filesize($file));
    flush();
    $fp = fopen($file, "r");
    while (!feof($fp)) {
      echo fread($fp, 65536);
      flush();
    } //!feof($fp)
    fclose($fp);
    unlink($file);
    //drupal_goto('flowsheeting-project/certificate');
    return;
  }

public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
}
}
?>
