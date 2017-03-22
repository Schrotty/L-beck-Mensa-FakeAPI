<?php
/**
 * Created by PhpStorm.
 * User: ruben
 * Date: 22.03.2017
 * Time: 01:25
 */

namespace api\core;

use DOMDocument;
use DOMXPath;

class Parser
{

    /**
     * @var string  contains the raw DOM
     */
    private $sRawDOMData;

    /**
     * @var array   contains the unformatted meal data
     */
    private $aMeals;

    /**
     * @var array   contains the unformatted info data
     */
    private $aInfo;

    /**
     * @var integer contains the current day index
     */
    private $iDayIndex;

    /**
     * @var object  contains the formatted meal data
     */
    private $oFormattedMealData;

    /**
     * @var object  contains the formatted meal data
     */
    private $oFormattedInfoData;

    /**
     * @var integer contains the index for 'FormatSingleDay'
     */
    private $iIndex;

    /**
     * __construct
     *
     * Parser constructor.
     *
     * @param $sParseURL    string  the URL to parse from
     */
    public function __construct($sParseURL){
        //echo "<pre>";

        $this->iIndex = 1;
        $this->iDayIndex = 0;
        $this->LoadDOMData($sParseURL);
        $this->FilterElements();
        $this->ParseMealData();
        $this->ParseInfoData();
    }

    /**
     * LoadMealData
     *
     * Return the formatted meal data
     *
     * @return string   the formatted meal data as JSON
     */
    public function LoadMealData(){
        return json_encode($this->oFormattedMealData);
    }

    /**
     * LoadInfoData
     *
     * Return the formatted info data
     *
     * @return  string  the formatted info data as JSON
     */
    public function LoadInfoData(){
        return json_encode($this->oFormattedInfoData);
    }

    /**
     * LoadDOMData
     *
     * Loads the needed DOM data from $sParseURL
     *
     * @param $sParseURL    string  the URL to parse from
     */
    private function LoadDOMData($sParseURL){
        $doc = new DOMDocument();

        // We don't want to bother with white spaces
        $doc->preserveWhiteSpace = false;

        // Most HTML Developers are chimps and produce invalid markup...
        $doc->strictErrorChecking = false;
        $doc->formatOutput = true;
        $doc->recover = true;

        $doc->loadHTMLFile($sParseURL, LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($doc);
        $query = "//div[@class='menu']";

        $entries = $xpath->query($query);
        $this->sRawDOMData = preg_replace('~\s+~i', ' ', $entries->item(0)->textContent);
    }

    /**
     * FilterElements
     *
     * Filter all unwanted elements from raw DOM
     */
    private function FilterElements(){
        $this->aMeals = explode("Gericht am ", trim(htmlentities($this->sRawDOMData)));
        $this->aInfo = explode("* Die", trim(htmlentities($this->aMeals[10])));

        $this->aMeals[10] = substr($this->aMeals[10], 0, strpos($this->aMeals[10], "* Die"));

        unset($this->aMeals[0]);
        unset($this->aInfo[0]);
    }

    /**
     * ParseInfoData
     *
     * Parses all needed info data into an object
     */
    private function ParseInfoData(){
        $aInfoArray = explode(" ", $this->aInfo[1]);
        $iMarkerIndex = 0;
        foreach($aInfoArray as $iKey => $sInfoString){
            if($iKey >=21 && $iKey <= 31) continue;

            if($iKey <= 20){
                $this->oFormattedInfoData->priceInfo .= " " . $sInfoString;
                continue;
            }

            if($iKey >= 32 && $iKey <= 127){
                if(is_numeric($sInfoString)){
                    $iMarkerIndex++;
                    $this->oFormattedInfoData->marker[$iMarkerIndex]->id = $sInfoString;
                    continue;
                }

                $this->oFormattedInfoData->marker[$iMarkerIndex]->description .= $sInfoString;
                continue;
            }

            if($iKey >= 128){
                $this->oFormattedInfoData->annotations .= " " . $sInfoString;
            }
        }
    }

    /**
     * ParseMealData
     *
     * Parses all needed meal data into an object
     */
    private function ParseMealData(){
        foreach($this->aMeals as $sMeal){
            $this->ParseSingleDay($this->FormatSingleDay($this->CreateDayArray($sMeal)));
        }
    }

    /**
     * CreateDayArray
     *
     * Create the unformatted day array
     *
     * @param $sRawData string  the raw data
     * @return          array   the unformatted days
     */
    private function CreateDayArray($sRawData){
        $aUnformattedDays = explode(" ", strip_tags($sRawData));
        unset($aUnformattedDays[1]);
        unset($aUnformattedDays[2]);

        return $aUnformattedDays;
    }

    /**
     * FormatSingleDay
     *
     * Format a single day
     *
     * @param $aUnformattedDay  array   the unformatted day
     * @return                  array   the formatted day
     */
    private function FormatSingleDay($aUnformattedDay){
        $aSortedDay = array();
        $iEuroIndex = 0;

        foreach($aUnformattedDay as $iKey => $sSingleLine) {
            if ($iKey == 0) {
                $aSortedDay[] = (integer)str_replace(".", "", $sSingleLine);
                continue;
            }

            if ($sSingleLine == "/") {
                continue;
            }

            if ($iEuroIndex < 3 && $sSingleLine != "&euro;") {
                $aSortedDay[(int)$this->iIndex][] = trim($sSingleLine, ',');
            }

            if ($sSingleLine == "&euro;") {
                $iEuroIndex++;
            }

            if ($iEuroIndex == 3) {
                $this->iIndex++;
                $iEuroIndex = 0;
            }
        }

        $this->iIndex = 1;
        unset($aSortedDay[count($aSortedDay) - 1]);

        return $aSortedDay;
    }

    /**
     * ParseSingleDay
     *
     * Parses a single day
     *
     * @param $aSingleDay   array   the single day
     */
    private function ParseSingleDay($aSingleDay){
        $iMealIndex = 0;

        foreach(array_filter($aSingleDay) as $iKey => $aValue){
            $this->ParseSingleMeal($aSingleDay[0], $aValue, $iMealIndex);
            $iMealIndex++;
        }

        $this->iDayIndex++;
    }

    /**
     * ParseSingleMeal
     *
     * Parses a single meal
     *
     * @param $sDate        integer the meals date
     * @param $aSingleMeal  array   the meal array
     * @param $iMealIndex   integer the current meal index
     */
    private function ParseSingleMeal($sDate, $aSingleMeal, $iMealIndex){
        $sDescription = "";
        foreach($aSingleMeal as $sMealValue){
            if(is_numeric($sMealValue)){
                $this->oFormattedMealData->day[$this->iDayIndex]->meal[$iMealIndex]->marker[] = $sMealValue;
            }

            if(is_numeric(str_replace(',', '.', $sMealValue))){
                if(strlen($sMealValue) > 2){
                    $this->oFormattedMealData->day[$this->iDayIndex]->meal[$iMealIndex]->prices[] = $sMealValue;
                }
            }

            if(!is_numeric(str_replace(',', '.', $sMealValue))){
                if($sMealValue[strlen($sMealValue)-1] == '-'){
                    $sDescription = $sDescription . $sMealValue;
                }else{
                    $sDescription = $sDescription . " " . $sMealValue;
                }
            }

            $this->oFormattedMealData->day[$this->iDayIndex]->meal[$iMealIndex]->date = $sDate;
            $this->oFormattedMealData->day[$this->iDayIndex]->meal[$iMealIndex]->description = $sDescription;
        }
    }
}