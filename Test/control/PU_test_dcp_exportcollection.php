<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\Pu;

require_once 'PU_testcase_dcp_commonfamily.php';
class ExportCollection extends TestCaseDcpCommonFamily
{
    static function getCommonImportFile()
    {
        return array(
            "PU_data_dcp_exportcollection.ods"
        );
    }
    
    protected $famName = "tst_expcoll1";
    /**
     * @param $separator
     * @param $enclosure
     * @param array $expectedData
     * @dataProvider dataExportCsv
     */
    public function testExportRawCsv($separator, $enclosure, array $expectedData)
    {
        $outFile = tempnam(getTmpDir() , 'tstexport');
        $s = new \SearchDoc(self::$dbaccess, $this->famName);
        $s->setObjectReturn();
        $s->search();
        
        $this->assertEmpty($s->searchError() , sprintf("Error in search %s", print_r($s->getSearchInfo() , true)));
        
        $ec = new \Dcp\ExportCollection();
        
        $ec->setCvsEnclosure($enclosure);
        $ec->setCvsSeparator($separator);
        $ec->setOutputFilePath($outFile);
        $ec->setDocumentlist($s->getDocumentList());
        $ec->export();
        
        $this->assertTrue(filesize($outFile) > 0, sprintf("\"%s\" file not produced", $outFile));
        $results = fopen($outFile, "r");
        $resultData = array();
        while (($data = fgetcsv($results, 1000, $separator, $enclosure)) !== FALSE) {
            $docName = $data[2];
            $resultData[$docName] = $data;
        }
        fclose($results);
        foreach ($expectedData as $docName => $docValues) {
            $this->assertTrue(isset($resultData[$docName]) , sprintf("%s document not found : %s", $docName, print_r($resultData, true)));
            foreach ($docValues as $index => $value) {
                $this->assertEquals($value, $resultData[$docName][$index], sprintf("%s  (index %s) : %s", $docName, $index, print_r($resultData, true)));
            }
        }
    }

    /**
     * @param $format
     * @param $separator
     * @param $enclosure
     * @param array $expectedData
     * @dataProvider dataExportDisplayCsv
     */
    public function testExportDisplayCsv($format, $separator, $enclosure, array $expectedData)
    {
        $outFile = tempnam(getTmpDir() , 'tstexport');
        $s = new \SearchDoc(self::$dbaccess, $this->famName);
        $s->setObjectReturn();
        $s->search();
        
        $this->assertEmpty($s->searchError() , sprintf("Error in search %s", print_r($s->getSearchInfo() , true)));
        
        $ec = new \Dcp\ExportCollection();
        
        $ec->setCvsEnclosure($enclosure);
        $ec->setCvsSeparator($separator);
        $ec->setOutputFilePath($outFile);
        $ec->setOutputFormat($format);
        $ec->setDocumentlist($s->getDocumentList());
        $ec->export();
        
        $this->assertTrue(filesize($outFile) > 0, sprintf("\"%s\" file not produced", $outFile));
        $results = fopen($outFile, "r");
        $resultData = array();
        while (($data = fgetcsv($results, 1000, $separator, $enclosure)) !== FALSE) {
            $docName = $data[0];
            $resultData[$docName] = $data;
        }
        fclose($results);
        foreach ($expectedData as $docName => $docValues) {
            $this->assertTrue(isset($resultData[$docName]) , sprintf("%s document not found : %s", $docName, print_r($resultData, true)));
            foreach ($docValues as $index => $value) {
                $this->assertEquals($value, $resultData[$docName][$index], sprintf("%s  (index %s) : %s \n %s", $docName, $index, print_r($resultData, true) , $outFile));
            }
        }
    }
    /**
     * @dataProvider dataExportXmlSingle
     */
    public function testExportXmlSingle(array $expectedData)
    {
        $outFile = tempnam(getTmpDir() , 'tstexport');
        $s = new \SearchDoc(self::$dbaccess, $this->famName);
        $s->setObjectReturn();
        $s->search();
        
        $this->assertEmpty($s->searchError() , sprintf("Error in search %s", print_r($s->getSearchInfo() , true)));
        
        $ec = new \Dcp\ExportCollection();
        
        $ec->setOutputFilePath($outFile);
        $ec->setOutputFormat(\Dcp\ExportCollection::xmlFileOutputFormat);
        $ec->setDocumentlist($s->getDocumentList());
        $ec->export();
        
        $this->assertTrue(filesize($outFile) > 0, sprintf("\"%s\" file not produced", $outFile));
        
        $dom = new \DOMDocument();
        $dom->load($outFile);
        
        $this->XPathTesting($dom, $expectedData);
    }
    /**
     * @dataProvider dataExportXmlArchive
     */
    public function testExportXmlArchive($file, array $xmlPathes)
    {
        $outFile = tempnam(getTmpDir() , 'tstexport');
        $s = new \SearchDoc(self::$dbaccess, $this->famName);
        $s->setObjectReturn();
        $s->search();
        
        $this->assertEmpty($s->searchError() , sprintf("Error in search %s", print_r($s->getSearchInfo() , true)));
        
        $ec = new \Dcp\ExportCollection();
        
        $ec->setOutputFilePath($outFile);
        $ec->setOutputFormat(\Dcp\ExportCollection::xmlArchiveOutputFormat);
        $ec->setDocumentlist($s->getDocumentList());
        $ec->export();
        
        $this->assertTrue(filesize($outFile) > 0, sprintf("\"%s\" file not produced", $outFile));
        
        $outDir = tempnam(getTmpDir() , 'tstextract');
        if (is_file($outDir)) {
            unlink($outDir);
        }
        mkdir($outDir);
        $zip = new \ZipArchive;
        $res = $zip->open($outFile);
        
        $this->assertTrue($res, sprintf("\"%s\" cannot unarchive", $outFile));
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (preg_match("/" . $file . "/", basename($stat['name']))) {
                $file = $stat['name'];
                break;
            }
        }
        
        $zip->extractTo($outDir, array(
            $file
        ));
        $zip->close();
        
        $xmlFile = sprintf("%s/%s", $outDir, $file);
        $this->assertTrue(is_file($xmlFile) , sprintf("\"%s\" zip content not found", $xmlFile));
        
        $dom = new \DOMDocument();
        $dom->load($xmlFile);
        
        $this->XPathTesting($dom, $xmlPathes);
    }
    
    protected function XPathTesting(\DOMDocument $dom, array $expectedValues)
    {
        
        $xp = new \DOMXpath($dom);
        foreach ($expectedValues as $path => $value) {
            $entries = $xp->query($path);
            $found = 0;
            $foundValues = array();
            
            foreach ($entries as $entry) {
                if ($entry->nodeValue == $value) $found++;
                $foundValues[] = $entry->nodeValue;
            }
            $this->assertGreaterThan(0, $found, sprintf("Item \"%s\" not found in %s path, found \n\t%s\n", $value, $path, implode("\n\t", $foundValues)));
        }
    }
    public function dataExportXmlArchive()
    {
        return array(
            array(
                "Titre1.*.xml",
                array(
                    "tst_frame1/tst_title" => "Titre 1",
                    "tst_frame1/tst_number" => "1",
                    "tst_frame1/tst_date" => "2014-02-23"
                )
            ) ,
            array(
                "Titre2.*.xml",
                array(
                    "tst_frame1/tst_number" => "2",
                    "tst_frame1/tst_date" => "2014-12-24"
                )
            ) ,
            array(
                "Titre3.*.xml",
                array(
                    "tst_frame1/tst_number" => "3",
                    "tst_tab_i/tst_frame2/tst_longtext" => "Trois long",
                    // "tst_tab_i/tst_frame2/tst_array/tst_othertexts" => "Une deuxième",
                    "tst_tab_i/tst_frame2/tst_array/tst_othertexts" => "Une ligne<BR>avec retour"
                )
            )
        );
    }
    public function dataExportXmlSingle()
    {
        return array(
            array(
                array(
                    $this->famName . "[@name = \"TST_EXPCOLL_DOC1\"]/tst_frame1/tst_number" => "1",
                    $this->famName . "[@name = \"TST_EXPCOLL_DOC1\"]/tst_frame1/tst_date" => "2014-02-23",
                    $this->famName . "[@name = \"TST_EXPCOLL_DOC2\"]/tst_frame1/tst_number" => "2"
                )
            )
        );
    }
    public function dataExportDisplayCsv()
    {
        return array(
            array(
                \Dcp\ExportCollection::csvDisplayValueOutputFormat,
                ";",
                '"',
                array(
                    "Titre 1" => array(
                        0 => "Titre 1",
                        1 => "1",
                        2 => "23/02/2014",
                        3 => "La",
                        4 => "Un",
                        5 => "1.1",
                        6 => "Un long"
                    ) ,
                    "Titre 2" => array(
                        0 => "Titre 2",
                        1 => "2",
                        2 => "24/12/2014",
                        3 => "Si",
                        4 => "Deux",
                        5 => "2.2",
                        6 => "Deux long",
                        7 => "Titre 1"
                    ) ,
                    "Titre 3" => array(
                        0 => "Titre 3",
                        7 => "Titre 1\nTitre 2",
                        8 => "Une ligne\navec retour\nUne deuxième"
                    )
                )
            ) ,
            array(
                \Dcp\ExportCollection::csvDisplayValueOutputFormat,
                ",",
                '"',
                array(
                    "Titre 1" => array(
                        0 => "Titre 1",
                        1 => "1",
                        2 => "23/02/2014",
                        3 => "La",
                        4 => "Un",
                        5 => "1.1",
                        6 => "Un long"
                    ) ,
                    "Titre 2" => array(
                        0 => "Titre 2",
                        1 => "2",
                        2 => "24/12/2014",
                        3 => "Si",
                        4 => "Deux",
                        5 => "2.2",
                        6 => "Deux long"
                    )
                )
            ) ,
            array(
                \Dcp\ExportCollection::csvRawOnlyDataOutputFormat,
                ";",
                '"',
                array(
                    "Titre 1" => array(
                        0 => "Titre 1",
                        1 => "1",
                        2 => "2014-02-23",
                        3 => "A",
                        4 => "Un",
                        5 => "1.1",
                        6 => "Un long"
                    ) ,
                    "Titre 2" => array(
                        0 => "Titre 2",
                        1 => "2",
                        2 => "2014-12-24",
                        3 => "B",
                        4 => "Deux",
                        5 => "2.2",
                        6 => "Deux long"
                    ) ,
                    "Titre 3" => array(
                        0 => "Titre 3",
                        7 => "TST_EXPCOLL_DOC1\nTST_EXPCOLL_DOC2",
                        8 => "Une ligne<BR>avec retour\nUne deuxième"
                    )
                )
            )
        );
    }
    public function dataExportCsv()
    {
        return array(
            array(
                ";",
                '"',
                array(
                    "TST_EXPCOLL_DOC1" => array(
                        4 => "Titre 1",
                        5 => "1",
                        6 => "2014-02-23",
                        7 => "A",
                        
                        8 => "Un",
                        9 => "1.1",
                        10 => "Un long"
                    ) ,
                    "TST_EXPCOLL_DOC2" => array(
                        4 => "Titre 2",
                        5 => "2",
                        6 => "2014-12-24",
                        7 => "B",
                        8 => "Deux",
                        9 => "2.2",
                        10 => "Deux long",
                        11 => "TST_EXPCOLL_DOC1"
                    ) ,
                    "TST_EXPCOLL_DOC3" => array(
                        4 => "Titre 3",
                        11 => "TST_EXPCOLL_DOC1\nTST_EXPCOLL_DOC2"
                    )
                )
            ) ,
            array(
                ",",
                "'",
                array(
                    "TST_EXPCOLL_DOC1" => array(
                        4 => "Titre 1",
                        5 => "1",
                        6 => "2014-02-23",
                        7 => "A",
                        8 => "Un",
                        9 => "1.1",
                        10 => "Un long"
                    ) ,
                    "TST_EXPCOLL_DOC2" => array(
                        4 => "Titre 2",
                        5 => "2",
                        6 => "2014-12-24",
                        7 => "B",
                        8 => "Deux",
                        9 => "2.2",
                        10 => "Deux long"
                    )
                )
            )
        );
    }
}
