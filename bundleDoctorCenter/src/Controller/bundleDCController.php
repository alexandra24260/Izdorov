<?php

	namespace Drupal\bundleDoctorCenter\Controller;

	use Symfony\Component\HttpFoundation\Response;
	use Drupal\Core\Controller\ControllerBase;
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Csv;

	class bundleDCController extends ControllerBase {

		public function bundleDC() {

			$PartOfLinks = $this->prepeareLinks();
			$listOfLinks = $this->getStreamCenter($PartOfLinks);
			$listOflinksFull = $this->getSpecializationCenter($listOfLinks);
			$output = $this->getListCenterDoctor($listOflinksFull);
			$this->saveDoctorsFile($output);
			$response = new Response();
			return $response;
		}
		/**
		 * Gets all list of bundle DoctorsCenter from
		 * https://medportal.org.
		 *
		 * @return array
		 */
		private function prepeareLinks(){
			$client   = \Drupal::httpClient();
			$request  = $client->get( 'https://medportal.org/minsk/clinics/' );
			$response = $request->getBody()->getContents();
			libxml_use_internal_errors( TRUE );
			$dom = new \DOMDocument();
			$dom->loadHTML( $response );
			$xpath = new \DOMXPath( $dom );
			$query = '//html/body/div/div/div/div/aside/div[@class="specialization"]/ul/li/a/@href';
			$tags           = $xpath->query( $query);
			$PartOfLinks = $this ->getHref($tags);
			return $PartOfLinks;
		}
		/**
		 * get href attribut of links DoctorsCenter .
		 */
		private function getHref($tags) {
			$listOfHref = [];
			foreach ($tags as $tag ) {
				$listOfHref[] = array('0'=>$tag->nodeValue);
			}
			return $listOfHref;
		}
		private function getStreamCenter($PartOfLinks){
			$client   = \Drupal::httpClient();
			foreach ($PartOfLinks as $tag){
				$request  = $client->get( 'https://medportal.org' . $tag[0] );
				$response = $request->getBody()->getContents();
				$dom      = new \DOMDocument();
				$dom->loadHTML( $response );
				$xpath = new \DOMXPath( $dom );
				$query = '//html/body/div/div/div/div/section/div/div/h3/a/@href';
				$tags  = $xpath->query( $query );
				foreach ($tags as $tag ) {
					$listOflinks[] = array(
						'0' => trim($tag->nodeValue));
				}
			}
			return $listOflinks;
		}
		private function getSpecializationCenter($listOfLinks){
			$client   = \Drupal::httpClient();
			foreach ($listOfLinks as $tag){
				$request  = $client->get( 'https://medportal.org' . $tag[0] );
				$response = $request->getBody()->getContents();
				$dom      = new \DOMDocument();
				$dom->loadHTML( $response );
				$xpath = new \DOMXPath( $dom );
				$query_link = '//html/body/div/div/div/div/section/div/ul/li/a/@href';
				$tags_link  = $xpath->query( $query_link );
				foreach ($tags_link as $tag ) {
					$listOflinksFull[] = array(
						'0' => trim($tag->nodeValue));
				}
			}
			return $listOflinksFull;
		}
		private function getListCenterDoctor($listOflinksFull){
			$client   = \Drupal::httpClient();
			foreach ($listOflinksFull as $tag){
				$request  = $client->get( 'https://medportal.org' . $tag[0] );
				$response = $request->getBody()->getContents();
				$dom      = new \DOMDocument();
				$dom->loadHTML( $response );
				$xpath = new \DOMXPath( $dom );
				$query = '//html/body/div/div/div/div/section/div/div/div';
				$tags  = $xpath->query( $query );
				foreach ($tags as $item ) {
					if ($item->textContent == "") {

					} else {
						$listOfDoctors[] = trim( $item->nodeValue );
					}
				}
			}
			return $listOfDoctors;
		}
		/**
		 * Save in csv file all list of bundle DoctorsCenter .
		 */
		private function saveDoctorsFile($output ) {
			$spreadsheet = new Spreadsheet();
			$spreadsheet->getActiveSheet()
			            ->fromArray( $output, NULL, 'A1' );
			$writer = new Csv( $spreadsheet );
			$writer->setDelimiter( '$' );
			$writer->setEnclosure( '$' );
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
	}
