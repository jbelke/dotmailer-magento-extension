<?php

class Dotdigitalgroup_Email_Adminhtml_ConnectorController extends Mage_Adminhtml_Controller_Action
{
    /**
     * AutoCreate and map datafields.
     */
    public function setupdatafieldsAction()
    {
        $result = array('errors' => false, 'message' => '');
	    $websiteParam = $this->getRequest()->getParam('website', 0);
	    $website = Mage::app()->getWebsite($websiteParam);
	    $apiModel = Mage::helper('connector')->getWebsiteApiClient($website->getId());
	    $redirectUrl = Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit', array('section' => 'connector_data_mapping'));

        // get all possible datatifileds
        $datafields = Mage::getModel('email_connector/connector_datafield')->getContactDatafields();
        foreach ($datafields as $key => $datafield) {
            $response = $apiModel->postDataFields($datafield, 'Private');

            //ignore existing datafields message
            if (isset($response->message) && $response->message != Dotdigitalgroup_Email_Model_Apiconnector_Client::REST_API_DATAFILEDS_EXISTS) {
                $result['errors'] = true;
                $result['message'] .=  ' Datafield ' . $datafield['name'] . ' - '. $response->message . '</br>';
            } else {
	            if ($websiteParam) {
                    $scope = 'websites';
                    $scopeId = $website->getId();
                } else {
                    $scope = 'default';
                    $scopeId = '0';
                }
                /**
                 * map the succesful created datafield
                 */
                $config = new Mage_Core_Model_Config();
	            $config->saveConfig('connector_data_mapping/customer_data/' . $key, strtoupper($datafield['name']), $scope, $scopeId);
                Mage::helper('connector')->log('successfully connected : ' . $datafield['name']);
            }
        }
        if ($result['errors']) {
            Mage::getSingleton('adminhtml/session')->addNotice($result['message']);
        } else {
            Mage::getConfig()->cleanCache();
            Mage::getSingleton('adminhtml/session')->addSuccess('All Datafields Created And Mapped.');
        }

        $this->_redirectUrl($redirectUrl);
    }

    /**
     * Reset order for reimport.
     */
    public function resetordersAction()
    {
        $num = Mage::getModel('email_connector/order')->resetOrders();
        Mage::helper('connector')->log('-- Reset Orders for reimport : ' . $num);
        Mage::getSingleton('adminhtml/session')->addSuccess('Done.');
        $this->_redirectReferer();
    }

	/**
	 * Reset customers import.
	 */
	public function resetcustomersimportAction()
	{
		Mage::getModel('email_connector/contact')->resetAllContacts();

		Mage::getSingleton('adminhtml/session')->addSuccess('Done.');

		$this->_redirectReferer();
	}

    /**
     * Refresh suppressed contacts.
     */
    public function suppresscontactsAction()
    {
        Mage::getModel('email_connector/newsletter_subscriber')
            ->unsubscribe(true);
        Mage::getSingleton('adminhtml/session')->addSuccess('Done.');
        $this->_redirectReferer();

    }
    /**
     * Remove contact id's.
     */
    public function deletecontactidsAction()
    {
        /** @var $coreResource Mage_Core_Model_Resource */
        $coreResource = Mage::getSingleton('core/resource');

        /** @var $conn Varien_Db_Adapter_Pdo_Mysql */
        $conn = $coreResource->getConnection('core_write');
        try{
            $num = $conn->update($coreResource->getTableName('email_connector/contact'),
                array('contact_id' => new Zend_Db_Expr('null')),
                $conn->quoteInto('contact_id is ?', new Zend_Db_Expr('not null'))
            );
        }catch (Exception $e){
            Mage::logException($e);
        }
        Mage::getSingleton('adminhtml/session')->addSuccess('Number Of Contacts Id Removed: '. $num);
        $this->_redirectReferer();
    }

	/**
	 * Ajax API validation.
	 */
	public function ajaxvalidationAction()
    {
        $params = $this->getRequest()->getParams();
        $apiUsername     = $params['api_username'];
	    // use javascript btoa function to encode the password

	    $apiPassword     = base64_decode($params['api_password']);
	    $message = Mage::getModel('email_connector/apiconnector_test')->ajaxvalidate($apiUsername, $apiPassword);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($message));
    }

	/**
	 * Ajax request to reset the import for contacts.
	 */
	public function resetcontactsajaxAction()
	{
		$numReseted = Mage::getModel('email_connector/contact')->resetAllContacts();
		$message = array('reseted' => $numReseted);
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($message));
	}

	/**
	 * Ajax requets to reset susbcribers for reimport.
	 */
	public function ajaxresetsubscribersAction()
	{
		$num = Mage::getModel('email_connector/contact')->resetSubscribers();
		$message = array('reseted' => $num);
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($message));
	}

	/**
	 * Ajax request to reset orders for reimoport.
	 */
	public function ajaxresetguestsAction()
	{
		$num = Mage::getModel('email_connector/order')->resetOrders();
		$message = array('reseted' => $num);
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($message));
	}

    public function createnewdatafieldAction()
    {
	    //get params required for datafield
	    $request = $this->getRequest();
	    $name    = $request->getParam('name', false);
	    $type    = $request->getParam('type', false);
	    $default = $request->getParam('default', 0);
		$access  = $request->getParam('access', false);
	    $website = $request->getParam('website', 0);

	    //api client for this website
        $client = Mage::helper('connector')->getWebsiteApiClient($website);
	    //only if all data is available
        if ($name && $type && $access) {
	        //create datafield
            $response = $client->postDataFields($name, $type, $access, $default);
	        //error creating datafield message
            if (isset($response->message)) {
	            //send error message to backend
                Mage::getSingleton('adminhtml/session')->addError($response->message);
                Mage::helper('connector')->log($response->message);
            } else {
	            //success message
                Mage::getSingleton('adminhtml/session')->addSuccess('Datafield created : ' . $name);
            }
        } else {
	        $message = 'Name ' . $name . ', type ' . $type . ' default ' . $default . 'access ' . $access;
            Mage::getSingleton('adminhtml/session')->addError('Datafield cannot be empty.');
	        Mage::helper('connector')->rayLog('100', $message);
        }
    }

	/**
	 * Create new address book action.
	 */
    public function createnewaddressbookAction()
    {
        $addressBookName = $this->getRequest()->getParam('name');
	    $visibility = $this->getRequest()->getParam('visibility');
        $website  = $this->getRequest()->getParam('website', 0);
        $client = Mage::helper('connector')->getWebsiteApiClient($website);
        if (strlen($addressBookName)) {
            $response = $client->postAddressBooks($addressBookName, $visibility);
            if (isset($response->message))
                Mage::getSingleton('adminhtml/session')->addError($response->message);
            else
                Mage::getSingleton('adminhtml/session')->addSuccess('Address book : '. $addressBookName . ' created.');
        }

    }

    public function reimoprtsubscribersAction()
    {
        $updated = Mage::getModel('email_connector/contact')->resetSubscribers();
        if ($updated) {
            Mage::getSingleton('adminhtml/session')->addSuccess('Done.');
        } else {
            Mage::getSingleton('adminhtml/session')->addNotice('No subscribers imported!');
        }
        $this->_redirectReferer();
    }

	/**
	 * path constant for config helper sent as string.
	 */
	public function enablewebsiteconfigurationAction()
	{
		$path       = $this->getRequest()->getParam('path');
		$value      = $this->getRequest()->getParam('value');
		$website    = $this->getRequest()->getParam('website', 0);

		$path = constant('Dotdigitalgroup_Email_Helper_Config::' . $path);
		$scope = 'websites';
		$scopeId = $website;

		$config = Mage::getConfig();

		//use value 1 if not set
        if (isset($value))
		    $config->saveConfig($path, $value, $scope, $scopeId);
        else
            $config->saveConfig($path, 1, $scope, $scopeId);

		//clean cache
		$config->cleanCache();

		$this->_redirectReferer();
	}

	/**
	 * Populate the tables (customer-email_contact, subscribers-email_contact) with missing ones.
	 */
	public function populatecontactsAction()
	{

		//type of data to bring up-to-date
		$type = $this->getRequest()->getParam('type', false);
		$website = $this->getRequest()->getParam('website', false);
		//required data not set
		if (!$type && $website == false) {
			return ;
		}

		$contactTable = Mage::getSingleton('core/resource')->getTableName('email_connector/contact');

		$customerCollection = Mage::getModel('customer/customer')->getCollection()
			->addFieldToFilter('website_id', $website);

		$customerCollection->getSelect()
			->joinLeft(array('ec' => $contactTable), 'e.entity_id = ec.customer_id', array('customer_id' => 'ec.customer_id'))
			->where('ec.customer_id IS NULL');

		//found customers t
		if ($count = $customerCollection->getSize()) {
			//trigger the save to update the contact table
			foreach ( $customerCollection as $customer ) {
				$customer->save();
			}

			Mage::getSingleton( 'adminhtml/session' )->addSuccess( "Total contacts populated : " . $count );
		}
		$this->_redirectReferer();
	}

	/**
	 * Trigger to run the contact sync.
	 */
	public function runcontactsyncAction()
	{
		$result = Mage::getModel('email_connector/cron')->contactSync();

		if ($result['message'])
			Mage::getSingleton('adminhtml/session')->addSuccess($result['message']);

		$this->_redirectReferer();
	}

	/**
	 * Trigger to run the subscriber sync.
	 */
	public function runsubscribersyncAction()
	{
		$result = Mage::getModel('email_connector/cron')->subscribersAndGuestSync();

		if ($result['message'])
			Mage::getSingleton('adminhtml/session')->addSuccess($result['message']);

		$this->_redirectReferer();
	}

	/**
	 * Trigger to run the order sync.
	 */
	public function runordersyncAction()
	{

		$result = Mage::getModel('email_connector/cron')->orderSync();
		if ($result['message'])
			Mage::getSingleton('adminhtml/session')->addSuccess($result['message']);

		$this->_redirectReferer();
	}

    /**
     * Trigger to run the review sync.
     */
    public function runreviewsyncAction()
    {

        $result = Mage::getModel('email_connector/cron')->reviewSync();
        if ($result['message'])
            Mage::getSingleton('adminhtml/session')->addSuccess($result['message']);

        $this->_redirectReferer();
    }

    /**
     * Trigger to run the reviw sync.
     */
    public function runwishlistsyncAction()
    {

        $result = Mage::getModel('email_connector/wishlist')->sync();
        if ($result['message'])
            Mage::getSingleton('adminhtml/session')->addSuccess($result['message']);

        $this->_redirectReferer();
    }

    /**
     * Trigger to run the quote sync.
     */
    public function runquotesyncAction()
    {

        $result = Mage::getModel('email_connector/cron')->quoteSync();
        if ($result['message'])
            Mage::getSingleton('adminhtml/session')->addSuccess($result['message']);

        $this->_redirectReferer();
    }

    /**
     * Reset quote for reimport.
     */
    public function resetquotesAction()
    {
        $num = Mage::getModel('email_connector/quote')->resetQuotes();
        Mage::helper('connector')->log('-- Reset Quotes for reimport : ' . $num);
        Mage::getSingleton('adminhtml/session')->addSuccess('Done.');
        $this->_redirectReferer();
    }

    /**
     * Reset reviews for reimport.
     */
    public function resetreviewsAction()
    {
        $num = Mage::getModel('email_connector/review')->reset();
        Mage::helper('connector')->log('-- Reset Reviews for reimport : ' . $num);
        Mage::getSingleton('adminhtml/session')->addSuccess('Done.');
        $this->_redirectReferer();
    }

    /**
     * Reset wishlist for reimport.
     */
    public function resetwishlistsAction()
    {
        $num = Mage::getModel('email_connector/wishlist')->reset();
        Mage::helper('connector')->log('-- Reset Wishlist for reimport : ' . $num);
        Mage::getSingleton('adminhtml/session')->addSuccess('Done.');
        $this->_redirectReferer();
    }
}