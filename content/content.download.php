<?php
require_once(TOOLKIT . '/class.xsltpage.php');
require_once(TOOLKIT . '/class.jsonpage.php');
require_once(TOOLKIT . '/class.sectionmanager.php');
require_once(TOOLKIT . '/class.fieldmanager.php');
require_once(TOOLKIT . '/class.entrymanager.php');
require_once(TOOLKIT . '/class.entry.php');
require_once(BOOT . '/func.utilities.php');
require_once(EXTENSIONS . '/importexport/lib/php-export-data/php-export-data.class.php');
require_once(EXTENSIONS . '/importexport/lib/parsecsv-0.3.2/parsecsv.lib.php');
require_once(CORE . '/class.cacheable.php');
//ini_set('max_execution_time', 0);

class contentExtensionImportexportDownload extends JSONPage
{		  
			public function view()
			{	
				$this->download($_REQUEST);
				
			}
			private function download($filelocation){
				
					$type = $_REQUEST['type'];
					
					$file = 'data-'.time('e').'-'.date('z').'.'.$type;	
					if($type == 'json'){
						header('Content-Type: application/json');
					}elseif($type =='csv'){
						header("Content-type: text/csv");
					}elseif($type == 'xml'){						
						header ("Content-Type:text/xml");
					}else{
						header ("Content-Type:text/plain");
					}
					header("Content-Disposition: attachment; filename=$file");
					header("Pragma: no-cache");
					header("Expires: 0");
					ob_clean();
					flush();
					readfile($filelocation['file']); // outputs file 
					unlink($filelocation['file']); // once output complete remove tmp file
					exit;
			}

}

?>