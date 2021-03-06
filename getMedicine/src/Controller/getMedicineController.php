<?php
	/**
	 * Created by PhpStorm.
	 * User: Alexandra_Vecher
	 * Date: 2/4/2019
	 * Time: 12:53 PM
	 */

	namespace Drupal\getMedicine\Controller;

	use Symfony\Component\HttpFoundation\Response;
	use Drupal\Core\Controller\ControllerBase;
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Csv;

	class getMedicineController extends ControllerBase {

		public function getMedicine() {

			$listOfMedicine = $this->prepeareMedicine();
			$this->saveMedicineFile($listOfMedicine);
			$response = new Response();
			return $response;

		}

		/**
		 * Gets all list of medicine from
		 * https://apteka.103.by/lekarstva-minsk/.
		 *
		 * @return array
		 */

		private function prepeareMedicine(){
			$client   = \Drupal::httpClient();
			$request  = $client->get( 'https://apteka.103.by/lekarstva-minsk/' );
			$response = $request->getBody()->getContents();
			libxml_use_internal_errors( TRUE );
			$dom = new \DOMDocument();
			$dom->loadHTML( $response );
			$xpath = new \DOMXPath( $dom );
			$query = '//ul[@class="list"]/li';
			$tags = $xpath->query( $query);
			$listOfMedicine = $this ->addHashMedicine($tags);
			return $listOfMedicine;
		}

		/**
		 * Save in csv file all list of Medicine .
		 */
		private function saveMedicineFile( $listOfMedicine ) {

			$spreadsheet = new Spreadsheet();
			$spreadsheet->getActiveSheet()
			            ->fromArray( $listOfMedicine, NULL, 'A1' );
			$writer = new Csv( $spreadsheet );
			$writer->setDelimiter( ';' );
			$writer->setEnclosure( '' );
			$writer->setLineEnding( "\r\n" );
			$writer->setSheetIndex( 0 );
			$filePath = date("m.d.y") .  "_listOfMedicine" . rand( 0,
					getrandmax() ) . ".csv";
			try {
				$writer->save( $filePath );
				echo "Last update from https://apteka.103.by/lekarstva-minsk/ save in  web/$filePath   ";
			} catch (Exception $e) {
				echo 'Error .... ',  $e->getMessage(), "\n";
			}
			return $filePath;

		}
		/**
		 * Add hash with md5 to list of Medicine .
		 */
		private function addHashMedicine($tags) {
			$listOfMedicine = [];
			foreach ($tags as $tag ) {
				$listOfMedicine[] = array(
					'0' => trim($tag->nodeValue),
					'1' => md5($tag->nodeValue));
			}
			return $listOfMedicine;
		}
	}