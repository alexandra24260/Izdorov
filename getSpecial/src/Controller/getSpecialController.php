<?php

	namespace Drupal\getSpecial\Controller;

	use Symfony\Component\HttpFoundation\Response;
	use Drupal\Core\Controller\ControllerBase;
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Csv;

	class getSpecialController extends ControllerBase {

		public function getSpecial() {

			$listOfSpecial = $this->prepeareSpecial();
			$this->saveSpecialFile($listOfSpecial);
			$response = new Response();
			return $response;

		}

		/**
		 * Gets all list of medicine from
		 * https://doktora.by/vrachi-belarusi-po-specialnosti.
		 *
		 * @return array
		 */

		private function prepeareSpecial(){
			$client   = \Drupal::httpClient();
			$request  = $client->get( 'https://doktora.by/vrachi-belarusi-po-specialnosti' );
			$response = $request->getBody()->getContents();
			libxml_use_internal_errors( TRUE );
			$dom = new \DOMDocument();
			$dom->loadHTML( $response );
			$xpath = new \DOMXPath( $dom );
			$query          = '//ul[@id="speciality"]/li';
			$tags           = $xpath->query( $query);
			$listOfSpecial = $this ->addHashSpecial($tags);
			return $listOfSpecial;
		}

		/**
		 * Save in csv file all list of Specialisation .
		 */
		private function saveSpecialFile( $listOfSpecial ) {

			$spreadsheet = new Spreadsheet();
			$spreadsheet->getActiveSheet()
			            ->fromArray( $listOfSpecial, NULL, 'A1' );
			$writer = new Csv( $spreadsheet );
			$writer->setDelimiter( ';' );
			$writer->setEnclosure( '' );
			$writer->setLineEnding( "\r\n" );
			$writer->setSheetIndex( 0 );
			$filePath = date("m.d.y") .  "_listOfSpecial" . rand( 0,
					getrandmax() ) . ".csv";
			try {
				$writer->save( $filePath );
				echo "Last update from https://doktora.by/vrachi-belarusi-po-specialnosti save in  web/$filePath   ";
			} catch (Exception $e) {
				echo 'Error .... ',  $e->getMessage(), "\n";
			}
			return $filePath;

		}
		/**
		 * Add hash with md5 to list of Doctors Specialisation  .
		 */
		private function addHashSpecial($tags) {
			$listOfSpecial = [];
			foreach ($tags as $tag ) {
				$listOfSpecial[] = array(
					'0' => trim($tag->nodeValue),
					'1' => md5($tag->nodeValue));
			}
			return $listOfSpecial;
		}

	}