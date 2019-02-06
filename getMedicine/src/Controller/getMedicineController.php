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

		/**
		 * Gets all list of medicine from
		 * https://apteka.103.by/lekarstva-minsk/.
		 *
		 * @return array
		 */
		public function getMedicine() {

			$client   = \Drupal::httpClient();
			$request  = $client->get( 'https://apteka.103.by/lekarstva-minsk/' );
			$response = $request->getBody()->getContents();
			libxml_use_internal_errors( TRUE );
			$dom = new \DOMDocument();
			$dom->loadHTML( $response );
			$xpath          = new \DOMXPath( $dom );
			$ul             = $dom->getElementsByTagName( 'ul[@class="list"]' )
			                      ->item( 0 );
			$query          = '//ul[@class="list"]/li';
			$tags           = $xpath->query( $query, $ul );
			$listOfMedicine = [];
			foreach ( $tags as $tag ) {
				$listOfMedicine[] = $tag->nodeValue;
			}
			$this->saveMedicineFile( $listOfMedicine );
			$response = new Response();

			return $response;
		}

		/**
		 * Save in csv file all list of Medicine .
		 */
		public function saveMedicineFile( $listOfMedicine ) {
			$spreadsheet = new Spreadsheet();
			$spreadsheet->getActiveSheet()
			            ->fromArray( $listOfMedicine, NULL, 'A1' );
			$writer = new Csv( $spreadsheet );
			$writer->setDelimiter( ';' );
			$writer->setEnclosure( '' );
			$writer->setLineEnding( "\r\n" );
			$writer->setSheetIndex( 0 );
			$filePath = '' . rand( 0, getrandmax() ) . rand( 0,
					getrandmax() ) . ".csv";
			$writer->save( $filePath );
			if ( $writer != NULL ) {
				echo "Last update from https://apteka.103.by/lekarstva-minsk/ save in  web/$filePath   ";
			} else {
				echo "Error ....";
			}
			return $filePath;

		}
	}