<?php

namespace LumturoNet\ContaoPersonioBundle\Classes;

use LumturoNet\ContaoPersonioBundle\Helpers;
use LumturoNet\ContaoPersonioBundle\Traits\Reader;

class RecruitingCategory
{
    use Reader;

    public function getRecruitingCategoriesAsOptions()
    {
        $arrVacancies = $this->getXml();

        return array_values(array_unique(Helpers::array_pluck($arrVacancies, 'recruitingCategory')));
    }
}
