<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2013 
 * @package    randomAlias
 * @license    GNU/LGPL
 * @filesource
 */

/**
 * Palettes 
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['randomAlias'] = '{type_legend},type;{include_legend},randomAlias;{protected_legend:hide},protected;{expert_legend:hide},guests,invisible,cssID,space';


$GLOBALS['TL_DCA']['tl_content']['fields']['randomAlias'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['randomAlias'],
    'inputType' => 'multiColumnWizard',
    'exclude'   => true,
    'eval'      => array(
        'width'        => '100%',
        'columnFields' => array(
            'article' => array(
                'label'            => &$GLOBALS['TL_LANG']['tl_content']['randomAlias_elements'],
                'exclude'          => true,
                'inputType'        => 'select',
                'options_callback' => array('tl_content_randomAlias', 'getContentElements'),
                'eval' => array(
                    'style'  => 'width:445px',
                    'chosen' => true
                )
            ),
            'type'   => array(
                'label'     => &$GLOBALS['TL_LANG']['tl_content']['randomAlias_type'],
                'exclude'   => true,
                'inputType' => 'select',
                'options'   => array(
                    'random',
                    'stand'
                ),
                'reference' => $GLOBALS['TL_LANG']['tl_content']['randomAlias_types'],
                'eval'      => array(
                    'style'  => 'width:145px',
                    'chosen' => true
                )
            ),
        )
    )
);

class tl_content_randomAlias extends Backend
{

    public function __construct()
    {
        parent::__construct();

        $this->import('BackendUser', 'User');
        $this->import('String');
    }

    /**
     * Get all content elements and return them as array (content element alias)
     * 
     * @return array
     */
    public function getContentElements()
    {
        $arrPids = array();
        $arrAlias = array();

        $strQuery = "SELECT c.id, c.pid, c.type, 
                (CASE c.type WHEN 'module' THEN m.name WHEN 'form' THEN f.title WHEN 'table' THEN c.summary ELSE c.headline END) AS headline, 
                c.text, a.title
            FROM tl_content c 
            LEFT JOIN tl_article a 
            ON a.id = c.pid 
            LEFT JOIN tl_module m 
            ON m.id = c.module 
            LEFT JOIN tl_form f 
            ON f.id = c.form\n";

        if (!$this->User->isAdmin)
        {
            foreach ($this->User->pagemounts as $id)
            {
                $arrPids[] = $id;
                $arrPids   = array_merge($arrPids, $this->getChildRecords($id, 'tl_page'));
            }

            if (empty($arrPids))
            {
                return $arrAlias;
            }

            $strQuery .= "WHERE a.pid 
                IN(" . implode(',', array_map('intval', array_unique($arrPids))) . ") 
                AND c.id != ? 
                ORDER BY a.title, c.sorting";
        }
        else
        {
            $strQuery .= "WHERE c.id != ? 
                ORDER BY a.title, c.sorting";
        }

        $objAlias = $this->Database
                ->prepare($strQuery)
                ->execute($this->Input->get('id'));

        while ($objAlias->next())
        {
            $arrHeadline = deserialize($objAlias->headline, true);

            if (isset($arrHeadline['value']))
            {
                $strHeadline = $this->String->substr($arrHeadline['value'], 32);
            }
            else
            {
                $strHeadline = $this->String->substr(preg_replace('/[\n\r\t]+/', ' ', $arrHeadline[0]), 32);
            }

            $strText = $this->String->substr(strip_tags(preg_replace('/[\n\r\t]+/', ' ', $objAlias->text)), 32);

            $arrTitle = array();
            $arrTitle[] = $GLOBALS['TL_LANG']['CTE'][$objAlias->type][0] . ' (';

            if ($strHeadline != '' && $strHeadline != 'NULL')
            {
                $arrTitle[] = $strHeadline . ', ';
            }
            elseif ($strText != '' && $strText != 'NULL')
            {
                $arrTitle[] = $strText . ', ';
            }

            $arrTitle[]                                                                 = 'ID ' . $objAlias->id . ')';
            $arrAlias[$objAlias->title . ' (ID ' . $objAlias->pid . ')'][$objAlias->id] = implode('', $arrTitle);
        }

        return $arrAlias;
    }

}

?>