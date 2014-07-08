<?php
require_once(TOOLKIT . '/class.xsltpage.php');
require_once(TOOLKIT . '/class.administrationpage.php');
require_once(TOOLKIT . '/class.sectionmanager.php');
require_once(TOOLKIT . '/class.fieldmanager.php');
require_once(TOOLKIT . '/class.entrymanager.php');
require_once(TOOLKIT . '/class.entry.php');
require_once(BOOT . '/func.utilities.php');
require_once(EXTENSIONS . '/importexport/lib/php-export-data/php-export-data.class.php');
require_once(EXTENSIONS . '/importexport/lib/parsecsv-0.3.2/parsecsv.lib.php');


ini_set('upload_max_filesize','70M');
class contentExtensionImportexportIndex extends AdministrationPage
{

/*    public function __construct(&$parent)
    {
        parent::__construct($parent);

    }
*/


    public function build()
    {
        parent::build();        
		parent::addStylesheetToHead(URL . '/extensions/importexport/assets/importexport.css');
		parent::addScriptToHead(URL . '/extensions/importexport/assets/import.js');
        parent::addScriptToHead(URL.'/extensions/importexport/assets/importexport.js', 70);
        
		$this->setTitle('Symphony - Import / Export');
        $this->Context->appendChild(new XMLElement('h2', __('Import / Export')));
    }


    public function view()
    {	
	
        if (isset($_POST['import-step-2']) && $_FILES['csv-file']['name'] != '') {
			
            // Import step 2:
            $this->__importStep2Page();
        } elseif (isset($_POST['import-step-3'])) {
            // Import step 3:
            $this->__importStep3Page();        
		} elseif( isset($_REQUEST['section'])){
			$this->__ajaxexport();
		}elseif (isset($_POST['ajax'])) {
            // Ajax import:
            $this->__ajaxImportRows();
        } elseif (isset($_POST['multilanguage-import']) && $_FILES['csv-file-ml']['name'] != '') {
            // Import multilanguage field:
            $this->__importMultiLanguage();
        } else {
            // Startpage:
            $this->__indexPage();
        }
    }


    private function __indexPage()
    {
        // Create the XML for the page:
        $xml = new XMLElement('data');
        $sectionsNode = new XMLElement('sections');
        $sm = new SectionManager($this);
        $sections = $sm->fetch();
        foreach ($sections as $section)
        {
            $sectionsNode->appendChild(new XMLElement('section', $section->get('name'), array('id' => $section->get('id'))));
        }
        $xml->appendChild($sectionsNode);

        // Check if the multilingual-field extension is installed:
        if(in_array('multilingual_field', ExtensionManager::listInstalledHandles()))
        {
            $xml->setAttribute('multilanguage', 'yes');
            // Get all the multilanguage fields:
            $fm = new FieldManager($this);
            $fields = $fm->fetch(null, null, 'ASC', 'sortorder', 'multilingual');
            $multilanguage = new XMLElement('multilanguage');
            foreach($fields as $field)
            {
                $sectionID = $field->get('parent_section');
                $section   = $sm->fetch($sectionID);
                $id        = $field->get('id');
                $label     = $section->get('name').' : '.$field->get('label');
                $multilanguage->appendChild(new XMLElement('field', $label, array('id'=>$id)));
            }
            $xml->appendChild($multilanguage);
        }

        // Generate the HTML:
        $xslt = new XSLTPage();
        $xslt->setXML($xml->generate());
        $xslt->setXSL(EXTENSIONS . '/importexport/content/index.xsl', true);
        $this->Form->setValue($xslt->generate());				
        $this->Form->setAttribute('enctype', 'multipart/form-data');
    }

    /**
     * Get the CSV object as it is stored in the database.
     * @return bool|mixed   the CSV object on success, false on failure
     */
    private function __getCSV($csv)
    {
			$fname = MANIFEST.'/tmp/data-1.json';			
			if(file_exists($fname)){
				unlink($fname);
			}			
			file_put_contents($fname,json_encode($csv->data));			
    }
	private function JsonToCsv($json){
			$fname = MANIFEST.'/tmp/data-2.json';		
			if(file_exists($fname)){
				unlink($fname);
			}			
			file_put_contents($fname,$json);							
	}
	private function __getXML($csv)
    {
			$fname = MANIFEST.'/tmp/data-3.xml';				
			if(file_exists($fname)){
				unlink($fname);
			}			
			file_put_contents($fname,$csv);			
    }
	private function importJson($json,$csvNode,$sectionID,$xml){
		
		$fm = new FieldManager();
		$t = array();		
		$json = json_decode($json);//explode('},{',str_replace(']','',str_replace('[','',$json)));
		$ja = array();
		
		foreach($json as $js => $j){
			$array = (array) $j;
			
			foreach($array as $arr => $ar){						
				$t[] = $arr;
			}
			
			
		}
		$t = array_unique($t);
		$count = new XMLElement('count',$linecount);
		foreach($t as $f => $field){
			if($f != 'id'){
							$csvNode->appendChild(new XMLElement('key', $field));
						}else{
							$csvNode->appendChild(new XMLElement('id', $field));
						}
						
		}
		$xml->appendChild($count);
	}
    private function __importStep2Page()
    {
        // Store the CSV data in the cache table, so the CSV file will not be stored on the server
		
        ///$cache = new Cacheable(Symphony::Database());
        // Get the nodes provided by this CSV file:
		$csv = new parseCSV();
		$csv->auto($_FILES['csv-file']['tmp_name']);
        $filename = $_FILES['csv-file']['name'];
		$tmpname = $_FILES['csv-file']['tmp_name'];
		$ext = pathinfo($_FILES['csv-file']['name'], PATHINFO_EXTENSION);	
		if($ext == 'json'){
			
			$this->JsonToCsv($csv->file_data);
			$fname = MANIFEST.'/tmp/data-2.json';	
			$data = file_get_contents($fname);			
			$fm = new FieldManager();
			$t = array();		
			$json = explode('},{',str_replace(']','',str_replace('[','',$data)));			
			$ja = array();
			foreach($json as $js => $jso){
				$ja[] = trim(str_replace('},','',str_replace('{','',$jso)));			
			}
			$json = '[{'.implode("},{",$ja).'}]';						
			$container = json_decode($json);
			$linecount = count($container);			
		}elseif($ext == 'csv'){			
			$this->__getCSV($csv);
			$linecount = count(file($_FILES['csv-file']['tmp_name']));
		}elseif($ext == 'xml'){
			
			$sxe = simplexml_load_file($_FILES['csv-file']['tmp_name']);
			
			$sxe = (array) $sxe;
			$this->__getXML($csv->file_data);
			$linecount = count($sxe['entry']);			
		}
		
        ///$cache->write('importcsv', serialize($csv), 60 * 60 * 24); // Store for one day
		
		
        $sectionID = $_POST['section'];

        // Generate the XML:
        $xml = new XMLElement('data', null, array('section-id' => $sectionID));

        // Get the fields of this section:
        $fieldsNode = new XMLElement('fields');
        $sm = new SectionManager($this);
        $section = $sm->fetch($sectionID);
        $fields = $section->fetchFields();
        foreach ($fields as $field)
        {
            $fieldsNode->appendChild(new XMLElement('field', $field->get('label'), array('id' => $field->get('id'))));
        }
        $xml->appendChild($fieldsNode);
		
        $csvNode = new XMLElement('csv');
		
		if($ext == 'json'){
		
		
			$json = $csv->file_data;			
			$this->importJson($json,$csvNode,$sectionID,$xml);
			
			
		}elseif($ext == 'csv'){
		
		
			$count = new XMLElement('count',$linecount);
			
			foreach ($csv->titles as $key)
			{
				//var_dump($key);
				//$key = explode(',',$key);
				if($key != 'id'){
					//foreach($key as $k => $ey){
						$csvNode->appendChild(new XMLElement('key', $key));
					//}
				}else{
					$csvNode->appendChild(new XMLElement('id', $key));
				}
			}
			
			$xml->appendChild($count);
			
			
		}elseif($ext =='xml'){
			$fname = MANIFEST.'/tmp/data-3.xml';			
			$se = (array) simplexml_load_file($fname);			
			$count = new XMLElement('count',$linecount);	
			$i = 0;
			foreach ($se['entry'] as $key)
			{					
				++$i;
				if($i <= 1){
					$key = (array) $key;
					foreach($key as $k => $ey){
						if($k != 'id'){
							$csvNode->appendChild(new XMLElement('key', $k));
						}else{
							$csvNode->appendChild(new XMLElement('id', $k));
						}
						
					}
				}
				
			}			
			$xml->appendChild($count);						
		}
		$fileNode = new XMLElement('file');
		$fileNode->appendChild(new XMLElement('location',$filename));
		$fileNode->appendChild(new XMLElement('tmp-location',$tmpname));
        $xml->appendChild($csvNode);	
		$xml->appendChild($fileNode);	
        // Generate the HTML:
        $xslt = new XSLTPage();
        $xslt->setXML($xml->generate());
        $xslt->setXSL(EXTENSIONS . '/importexport/content/step2.xsl', true);
        $this->Form->setValue($xslt->generate());
    }
	

    private function __addVar($name, $value)
    {
        $this->Form->appendChild(new XMLElement('var', $value, array('class' => $name)));
    }
	
    private function __exportMultiLanguage()
    {
        // Get the ID of the field which values should be exported:
        $fieldID = $_REQUEST['multilanguage-field-export'];

        // Get the languages:
        $supported_language_codes = $this->__getLanguages();

        // Create the CSV Headers:
        $csv = '"entry_id"';
        foreach($supported_language_codes as $code)
        {
            $csv .= ';"'.$code.'"';
        }
        $csv .= "\r\n";

        // Get the data of the field:
        $data    = Symphony::Database()->fetch('SELECT * FROM `tbl_entries_data_'.$fieldID.'`;');

        // Loop through the data:
        foreach($data as $row)
        {
            $entryID = $row['entry_id'];
            $csv .= '"'.$entryID.'"';
            foreach($supported_language_codes as $code)
            {
                $csv .= ';"'.str_replace('"', '""', $row['value-'.$code]).'"';
            }
            $csv .= "\r\n";
        }

        // Output the CSV:
        $fm = new FieldManager($this);
        $sm = new SectionManager($this);
        $field = $fm->fetch($fieldID);
        $section   = $sm->fetch($field->get('parent_section'));

        $fileName = 'export-'.strtolower($section->get('handle').'-'.$field->get('element_name')).'.csv';
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        echo $csv;
        die();
    }

    private function __getLanguages()
    {
        // Get the available languages:
        $supported_language_codes = explode(',', General::sanitize(Symphony::Configuration()->get('language_codes', 'language_redirect')));
        $supported_language_codes = array_map('trim', $supported_language_codes);
        $supported_language_codes = array_filter($supported_language_codes);
        return $supported_language_codes;
    }

    private function __importMultilanguage()
    {
        // Get the ID of the field which values should be imported:
        $fieldID = $_REQUEST['multilanguage-field-import'];

        // Get the nodes provided by this CSV file:
        $csv = new parseCSV();
        $csv->auto($_FILES['csv-file-ml']['tmp_name']);

        // Get the CSV Data:
        $csvData = $csv->data;

        // Get the languages:
        $supported_language_codes = $this->__getLanguages();

        // Itterate throught each row:
        $count = 0;
        foreach($csvData as $row)
        {
            if(isset($row['entry_id']))
            {
                $data = array();
                $first = true;
                // Itterate according to the languages that are available, not the ones that are defined in the CSV:
                foreach($supported_language_codes as $code)
                {
                    // Check if this language code exists in the CSV data:
                    if(isset($row[$code]))
                    {
                        // The first language in the CSV data is used as the default language:
                        if($first)
                        {
                            $data['handle'] = General::createHandle($row[$code]);
                            $data['value']  = General::sanitize($row[$code]);
                        }
                        // Store the value for this specific language:
                        $data['handle-'.$code]          = General::createHandle($row[$code]);
                        $data['value-'.$code]           = $row[$code];
                        $data['value_format-'.$code]    = $row[$code];
                        $data['word_count-'.$code]      = substr_count($row[$code], ' ') + 1;
                        $first = false;
                    }
                }
                // Update the data in the database:
                Symphony::Database()->update($data, 'tbl_entries_data_'.$fieldID, '`entry_id` = '.trim($row['entry_id']));
                $count++;
            }
        }

        // Show the message that the import was successfull.
        $this->Form->appendChild(new XMLElement('p', __('Import successfull: ').$count.' '.__('entries updated')));
        $p = new XMLElement('p');
        $p->appendChild(new XMLElement('a', __('Import another field'), array('href'=>'?#multi')));
        $this->Form->appendChild($p);
    }

}

