<?php

	namespace Drupal\getDoctors\Controller;

	use Symfony\Component\HttpFoundation\Response;
	use Drupal\Core\Controller\ControllerBase;
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Csv;

	class getDoctorsController extends ControllerBase {

		public function getDoctors() {

			$PartOfLinks = $this->prepeareLinks();
			$listOfDoctors = $this->getNameOfDoctors($PartOfLinks);
			$this->saveDoctorsFile($listOfDoctors);
			$response = new Response();
			return $response;

		}

		/**
		 * Gets all list of Doctors from
		 * https://doktora.by/vrachi-belarusi-po-specialnosti.
		 *
		 * @return array
		 */

		private function prepeareLinks(){
			$client   = \Drupal::httpClient();
			$request  = $client->get( 'https://doktora.by/vrachi-belarusi-po-specialnosti' );
			$response = $request->getBody()->getContents();
			libxml_use_internal_errors( TRUE );
			$dom = new \DOMDocument();
			$dom->loadHTML( $response );
			$xpath = new \DOMXPath( $dom );
			$query          = '//ul[@id="speciality"]/li/a/@href';
			$tags           = $xpath->query( $query);
			$PartOfLinks = $this ->getHref($tags);
			return $PartOfLinks;
		}

		/**
		 * Save in csv file all list of Doctors .
		 */
		private function saveDoctorsFile( $listOfDoctors ) {

			$spreadsheet = new Spreadsheet();
			$spreadsheet->getActiveSheet()
			            ->fromArray( $listOfDoctors, NULL, 'A1' );
			$writer = new Csv( $spreadsheet );
			$writer->setDelimiter( ';' );
			$writer->setEnclosure( '' );
			$writer->setLineEnding( "\r\n" );
			$writer->setSheetIndex( 0 );
			$filePath = date("m.d.y") .  "_listOfDoctors" . rand( 0,
					getrandmax() ) . ".csv";
			try {
				$writer->save( $filePath );
				echo "Last update from https://doktora.by save in  web/$filePath   ";
			} catch (Exception $e) {
				echo 'Error .... ',  $e->getMessage(), "\n";
			}
			return $filePath;

		}
		/**
		 * get href attribut of link Doctors .
		 */
		private function getHref($tags) {
			$listOfHref = [];
			foreach ($tags as $tag ) {
				$listOfHref[] = array('0'=>$tag->nodeValue);
			}
			return $listOfHref;
		}
		/**
		 * complite link to get list of Doctors
		 */
		private function getNameOfDoctors($PartOfLinks){
			$client   = \Drupal::httpClient();
			foreach ($PartOfLinks as $tag){
					$request  = $client->get( 'https://doktora.by/' . $tag[0] );
					$response = $request->getBody()->getContents();
					$dom      = new \DOMDocument();
					$dom->loadHTML( $response );
					$xpath = new \DOMXPath( $dom );
					$query = '//html/body/div/div/div/div/div/div/div/div/div/div/h2/a';
					$tags  = $xpath->query( $query );
						foreach ($tags as $tag ) {
							$listOfDoctors[] = array(
								'0' => trim($tag->nodeValue),
								'1' => md5($tag->nodeValue));
						}
				}
			return $listOfDoctors;
		}
	}