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
		$fname = MANIFEST.'/tmp/data.json';
		if($csv){			
			if(file_exists($fname)){
				unlink($fname);
			}
			
			file_put_contents($fname,json_encode($csv->data));
		}else{
			$data = file_get_contents($fname);
			$content = json_decode($data);
			$container->titles = $content[0];
			$container->data = $content;
			
		
			return $container;
		}		
    }
	

    private function __importStep2Page()
    {
        // Store the CSV data in the cache table, so the CSV file will not be stored on the server
		
        ///$cache = new Cacheable(Symphony::Database());
        // Get the nodes provided by this CSV file:
		
        $csv = new parseCSV();
        $csv->auto($_FILES['csv-file']['tmp_name']);
		
        ///$cache->write('importcsv', serialize($csv), 60 * 60 * 24); // Store for one day
		$this->__getCSV($csv);
		
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
        foreach ($csv->titles as $key)
        {
            $csvNode->appendChild(new XMLElement('key', $key));
        }
        $xml->appendChild($csvNode);					
        // Generate the HTML:
        $xslt = new XSLTPage();
        $xslt->setXML($xml->generate());
        $xslt->setXSL(EXTENSIONS . '/importcsv/content/step2.xsl', true);
        $this->Form->setValue($xslt->generate());
    }


    private function __addVar($name, $value)
    {
        $this->Form->appendChild(new XMLElement('var', $value, array('class' => $name)));
    }

    private function __importStep3Page()
    {
        // Store the entries:
        $sectionID = $_POST['section'];
        $uniqueAction = $_POST['unique-action'];
        $uniqueField = $_POST['unique-field'];
        $countNew = 0;
        $countUpdated = 0;
        $countIgnored = 0;
        $countOverwritten = 0;
        $fm = new FieldManager($this);		
        $csv = $this->__getCSV(false);
		
        // Load the information to start the importing process:
        $this->__addVar('section-id', $sectionID);
        $this->__addVar('unique-action', $uniqueAction);
        $this->__addVar('unique-field', $uniqueField);
        $this->__addVar('import-url', URL . '/symphony/extension/importexport/');

        // Output the CSV-data:
        $csvData = $csv->data;
		
        $csvTitles = $csv->titles;
        $this->__addVar('total-entries', count($csvData));

        // Store the associated Field-ID's:
        $i = 0;
        $ids = array();
        foreach ($csvTitles as $title)
        {
            $ids[] = $_POST['field-' . $i];
            $i++;
        }
        $this->__addVar('field-ids', implode(',', $ids));

        $this->addScriptToHead(URL . '/extensions/importexport/assets/import.js');
        $this->Form->appendChild(new XMLElement('h2', __('Import in progress...')));
        $this->Form->appendChild(new XMLElement('div', '<div class="bar"></div>', array('class' => 'progress')));
        $this->Form->appendChild(new XMLElement('div', null, array('class' => 'console')));
    }

    private function getDrivers()
    {
        $classes = glob(EXTENSIONS . '/importexport/drivers/*.php');
        $drivers = array();
        foreach ($classes as $class)
        {
            include_once($class);
            $a = explode('_', str_replace('.php', '', basename($class)));
            $driverName = '';
            for ($i = 1; $i < count($a); $i++)
            {
                if ($i > 1) {
                    $driverName .= '_';
                }
                $driverName .= $a[$i];
            }
            $className = 'ImportDriver_' . $driverName;
            $drivers[$driverName] = new $className;
        }
        return $drivers;
    }

    /**
     * This function imports 10 rows of the CSV data
     * @return void
     */
    private function __ajaxImportRows()
    {
        $messageSuffix = '';
        $updated = array();
        $ignored = array();

        $csv = $this->__getCSV(false);
		
        if ($csv != false) {
            // Load the drivers:
            $drivers = $this->getDrivers();

            // Default parameters:
            $currentRow = intval($_POST['row']);
            $sectionID = $_POST['section-id'];
            $uniqueAction = $_POST['unique-action'];
            $uniqueField = $_POST['unique-field'];
            $fieldIDs = explode(',', $_POST['field-ids']);
            $entryID = null;

            // Load the fieldmanager:
            $fm = new FieldManager($this);

            // Load the entrymanager:
            $em = new EntryManager($this);

            // Load the CSV data of the specific rows:
            $csvTitles = $csv->titles;
            $csvData = $csv->data;
            for ($i = $currentRow * 10; $i < ($currentRow + 1) * 10; $i++)
            {
                // Start by creating a new entry:
                $entry = new Entry($this);
                $entry->set('section_id', $sectionID);
				
                // Ignore this entry?
                $ignore = false;

                // Import this row:
                $row = $csvData[$i];
				
                if ($row != false) {

                    // If a unique field is used, make sure there is a field selected for this:
                    if ($uniqueField != 'no' && $fieldIDs[$uniqueField] == 0) {
                        die(__('[ERROR: No field id sent for: "' . $csvTitles[$uniqueField] . '"]'));
                    }

                    // Unique action:
                    if ($uniqueField != 'no') {
                        // Check if there is an entry with this value:
                        $field = $fm->fetch($fieldIDs[$uniqueField]);
                        $type = $field->get('type');
                        if (isset($drivers[$type])) {
                            $drivers[$type]->setField($field);
							
                            $entryID = $drivers[$type]->scanDatabase($row[$csvTitles[$uniqueField]]);
                        } else {
                            $drivers['default']->setField($field);
							
                            $entryID = $drivers['default']->scanDatabase($row[$csvTitles[$uniqueField]]);
                        }

                        if ($entryID != false) {
                            // Update? Ignore? Add new?
                            switch ($uniqueAction)
                            {
                                case 'update' :
                                    {
                                    $a = $em->fetch($entryID);
                                    $entry = $a[0];
                                    $updated[] = $entryID;
                                    break;
                                    }
                                case 'ignore' :
                                    {
                                    $ignored[] = $entryID;
                                    $ignore = true;
                                    break;
                                    }
                            }
                        }
                    }
				
                    if (!$ignore) {
                        // Do the actual importing:
                        $j = 0;
                        foreach ($row as $value)
                        {
                            // When no unique field is found, treat it like a new entry
                            // Otherwise, stop processing to safe CPU power.
                            $fieldID = intval($fieldIDs[$j]);
                            // If $fieldID = 0, then `Don't use` is selected as field. So don't use it! :-P
                            if ($fieldID != 0) {
                                $field = $fm->fetch($fieldID);
                                // Get the corresponding field-type:
                                $type = $field->get('type');
                                if (isset($drivers[$type])) {
                                    $drivers[$type]->setField($field);
                                    $data = $drivers[$type]->import($value, $entryID);
                                } else {
                                    $drivers['default']->setField($field);
                                    $data = $drivers['default']->import($value, $entryID);
                                }
                                // Set the data:
							
                                if ($data != false) {
                                    $entry->setData($fieldID, $data);
                                }
                            }
													
                            $j++;
                        }
						
                        // Store the entry:
                        $entry->commit();
                    }
                }
            }
        } else {
            die(__('[ERROR: Data not found!]'));
        }
		
        if (count($updated) > 0) {
            $messageSuffix .= ' ' . __('(updated: ') . implode(', ', $updated) . ')';
        }
        if (count($ignored) > 0) {
            $messageSuffix .= ' ' . __('(ignored: ') . implode(', ', $updated) . ')';
        }

        die('[OK]' . $messageSuffix);
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

