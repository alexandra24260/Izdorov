<?php
	/**
	 * Created by PhpStorm.
	 * User: Alexandra_Vecher
	 * Date: 2/4/2019
	 * Time: 12:53 PM
	 */

	namespace Drupal\getCenter\Controller;

	use Symfony\Component\HttpFoundation\Response;
	use Drupal\Core\Controller\ControllerBase;
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Csv;

	class getCenterController extends ControllerBase {

		public function getCenter() {

			$listOfCenter = $this->prepeareCenter();
			$this->saveCenterFile($listOfCenter);
			$response = new Response();
			return $response;

		}

		/**
		 * Gets all list of medicine from
		 * https://www.103.by/cat/med/medicinskie-centry/.
		 *
		 * @return array
		 */

		private function prepeareCenter(){
			$client   = \Drupal::httpClient();
			$request  = $client->get( 'https://www.103.by/cat/med/medicinskie-centry/' );
			$response = $request->getBody()->getContents();
			libxml_use_internal_errors( TRUE );
			$dom = new \DOMDocument();
			$dom->loadHTML('<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $response );
			$xpath = new \DOMXPath( $dom );
			$query = '//html/body/div/div/div/main/div/div/div/div/div/div/div/div/div/div/div/a';
			$tags = $xpath->query( $query);
			$listOfCenter = $this ->addHashCenter($tags);
			return $listOfCenter;
		}

		/**
		 * Save in csv file all list of Medicine .
		 */
		private function saveCenterFile( $listOfCenter ) {

			$spreadsheet = new Spreadsheet();
			$spreadsheet->getActiveSheet()
			            ->fromArray( $listOfCenter, NULL, 'A1' );
			$writer = new Csv( $spreadsheet );
			$writer->setDelimiter( ';' );
			$writer->setEnclosure( '' );
			$writer->setLineEnding( "\r\n" );
			$writer->setSheetIndex( 0 );
			$filePath = date("m.d.y") .  "_listOfMedicalCenter" . rand( 0,
				getrandmax() ) . ".csv";
			try {
				$writer->save( $filePath );
				echo "Last update from https://www.103.by/cat/med/medicinskie-centry/ save in  web/$filePath   ";
			} catch (Exception $e) {
				echo 'Error .... ',  $e->getMessage(), "\n";
			}
			return $filePath;

		}
		/**
		 * Add hash with md5 to list of Medical Center .
		 */
		private function addHashCenter($tags) {
			$listOfCenter = [];
			foreach ($tags as $tag ) {
				$listOfCenter[] = array(
					'0' => trim($tag->nodeValue),
					'1' => md5($tag->nodeValue));
			}
			return $listOfCenter;
		}
	}