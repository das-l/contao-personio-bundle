<?php

namespace LumturoNet\ContaoPersonioBundle\Traits;

use Contao\Config;
use Contao\System;
use InvalidArgumentException;
use LumturoNet\ContaoPersonioBundle\Helpers;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Trait Reader
 * @package LumturoNet\ContaoPersonioBundle\Traits
 */
trait Reader
{
    protected $strWsUrl = null;

    /**
     * @return mixed
     */
    private function getXml()
    {
        if ($this->strWsUrl === null) {
            $this->strWsUrl = Config::get('personio_webservice_url');
        }

        try {
            $cacheDir = System::getContainer()->getParameter('kernel.cache_dir');
        }
        catch (InvalidArgumentException $objException) {
            $cacheDir = '';
        }

        $objCache = new FilesystemAdapter('contao_personio', 0, $cacheDir);

        return $objCache->get('jobs', function(ItemInterface $objItem) {
            $cacheTime = 86400;
            if (($cacheTimeConfig = Config::get('personio_cache_time')) !== '') {
                $cacheTime = $cacheTimeConfig;
            }
            $objItem->expiresAfter($cacheTime);

            $objXml  = simplexml_load_file($this->strWsUrl, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($objXml === false) {
                return false;
            }

            $strJson = json_encode($objXml);
            $arrPositions = json_decode($strJson, true)['position'];
            // Only one position
            if (is_array($arrPositions) && !isset($arrPositions[0])) {
                $arrPositions = [$arrPositions];
            }

            return $arrPositions;
        });
    }

    /**
     * @return mixed
     */
    public function getVacancies()
    {
        return $this->getXml();
    }

    /**
     * @param $intId
     * @return mixed|null
     */
    public function getVacancyById($intId)
    {
        $arrData = $this->getXml();

        if ($arrData === false) {
            return false;
        }

        $intSearchKey = array_search($intId, Helpers::array_pluck($arrData, 'id'));

        return $arrData[$intSearchKey]['id'] == $intId ? $arrData[$intSearchKey] : null;
    }

    /**
     * @param $strCompany
     * @return array|false
     */
    public function getVacanciesByCompany($strCompany)
    {
        return $this->getVacanciesByFieldValue('subcompany', $strCompany);
    }

    /**
     * @param $strCompany
     * @return array|false
     */
    public function getVacanciesByRecruitingCategory($strRecruitingCategory)
    {
        return $this->getVacanciesByFieldValue('recruitingCategory', $strRecruitingCategory);
    }

    /**
     * @param $strField
     * @param $strValue
     * @return array|false
     */
    public function getVacanciesByFieldValue($strField, $strValue)
    {
        $arrData = $this->getXml();

        if ($arrData === false) {
            return false;
        }

        $arrVacancies = [];
        foreach($arrData as $intIndex => $arrVacancy) {
            if($arrVacancy[$strField] == $strValue) $arrVacancies[] = $arrVacancy;
        }

        return $arrVacancies;
    }
}
