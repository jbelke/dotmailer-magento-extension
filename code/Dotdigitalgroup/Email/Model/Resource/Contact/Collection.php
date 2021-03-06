<?php

class Dotdigitalgroup_Email_Model_Resource_Contact_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
	/**
	 * constructor.
	 */
	protected function _construct()
	{
        parent::_construct();
        $this->_init('email_connector/contact');
    }


    /**
     * @param $website
     * @return $this
     */
    public function addWebsiteFilter($website)
    {
        $this->addFilter('website_id', $website);
        return $this;
    }

}