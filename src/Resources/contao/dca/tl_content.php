<?php
$GLOBALS['TL_DCA']['tl_content']['fields']['personio_company'] = [
    'label'      => ['Firma', 'Wählen Sie hier eine Firma, von der Stellenangebote ausgegeben werden sollen.'],
    'exclude'    => true,
    'inputType'  => 'select',
    'options_callback'    => ['LumturoNet\ContaoPersonioBundle\Classes\Company', 'getCompaniesAsOptions'],
    'eval'       => array
    (
        'mandatory'          => false,
        'includeBlankOption' => true,
        'tl_class'           => 'clr'
    ),
    'sql'        => "varchar(255) NULL default ''"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['personio_recruiting_category'] = [
    'label'      => ['Recruiting-Kategorie', 'Wählen Sie hier eine Recruiting-Kategorie, aus der Stellenangebote ausgegeben werden sollen.'],
    'exclude'    => true,
    'inputType'  => 'select',
    'options_callback'    => ['LumturoNet\ContaoPersonioBundle\Classes\RecruitingCategory', 'getRecruitingCategoriesAsOptions'],
    'eval'       => array
    (
        'mandatory'          => false,
        'includeBlankOption' => true,
        'tl_class'           => 'clr'
    ),
    'sql'        => "varchar(255) NULL default ''"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['personio_vacancy_detailpage'] = [
    'label'      => ['Detailseite', 'Wählen Sie hier eine Detailseite für das Stellenangebot.'],
    'exclude'    => true,
    'inputType'  => 'pageTree',
    'eval'       => array
    (
        'mandatory' => false,
        'tl_class'  => 'clr'
    ),
    'sql'        => "int(10) unsigned NOT NULL default 0"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['personio_jumpTo'] = [
    'label'      => ['Weiterleitungsseite', 'Wählen Sie hier eine Seite aus, auf die nach Absenden des Bewerbungsformular weitergeleitet werden soll.'],
    'exclude'    => true,
    'inputType'  => 'pageTree',
    'eval'       => array
    (
        'mandatory' => false,
        'tl_class'  => 'clr'
    ),
    'sql'        => "int(10) unsigned NOT NULL default 0"
];

$GLOBALS['TL_DCA']['tl_content']['palettes']['personio_vacancies'] =
    '{type_legend},type,personio_company,personio_recruiting_category,personio_vacancy_detailpage,personio_jumpTo;' .
    '{expert_legend:hide},cssID;' .
    '{invisible_legend:hide},invisible';
