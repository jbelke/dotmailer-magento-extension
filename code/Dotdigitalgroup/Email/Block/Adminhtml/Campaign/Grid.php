<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Campaign_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('id');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
	 *  Prepare grid collection object.
	 * @return Mage_Adminhtml_Block_Widget_Grid
	 */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('email_connector/campaign')->getCollection();
        $this->setCollection($collection);
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header'        => Mage::helper('connector')->__('Campaign ID'),
            'width'         => '20px',
            'index'         => 'campaign_id',
            'type'          => 'number',
            'truncate'      => 50,
            'escape'        => true,
        ))->addColumn('email', array(
            'header'        => Mage::helper('connector')->__('Email'),
            'align'         => 'left',
            'width'         => '50px',
            'index'         => 'email',
            'type'          => 'text',
            'escape'        => true
        ))->addColumn('is_created', array(
            'header'        => Mage::helper('connector')->__('Is Created'),
            'align'         => 'center',
            'width'         => '20px',
            'index'         => 'is_created',
            'escape'        => true,
	        'type'          => 'options',
            'renderer'     => 'email_connector/adminhtml_column_renderer_imported',
            'options'       => array(
	            '1'    => 'Is Created',
	            'null' => 'Not Created'
            ),
            'filter_condition_callback' => array($this, 'filterCallbackContact')
        ))->addColumn('is_sent', array(
            'header'        => Mage::helper('connector')->__('Is Sent'),
            'align'         => 'center',
            'width'         => '20px',
            'index'         => 'is_sent',
            'escape'        => true,
            'type'          => 'options',
            'renderer'     => 'email_connector/adminhtml_column_renderer_imported',
            'options'       => array(
                '1'    => 'Is Send',
                'null' => 'Not Send'
            ),
            'filter_condition_callback' => array($this, 'filterCallbackContact')
        ))->addColumn('is_copy', array(
            'header'        => Mage::helper('connector')->__('Is Copy'),
            'align'         => 'center',
            'width'         => '20px',
            'index'         => 'is_copy',
            'escape'        => true,
            'type'          => 'options',
            'renderer'     => 'email_connector/adminhtml_column_renderer_imported',
            'options'       => array(
                '1'    => 'Is A Copy',
                'null' => 'Not A Copy'
            ),
            'filter_condition_callback' => array($this, 'filterCallbackContact')
        ))->addColumn('order_increment_id', array(
            'header'        => Mage::helper('connector')->__('Increment ID'),
            'align'         => 'left',
            'width'         => '50px',
            'index'         => 'order_increment_id',
            'type'          => 'number',
            'escape'        => true
        ))->addColumn('create_message', array(
            'header'		=> Mage::helper('connector')->__('Create Message'),
            'align'		=> 'left',
            'width'		=> '300px',
            'index'     => 'create_message',
            'type'      => 'text',
            'escape'    => true
        ))->addColumn('contact_message', array(
            'header'		=> Mage::helper('connector')->__('Contact Message'),
            'align'		=> 'left',
            'width'		=> '300px',
            'index'     => 'contact_message',
            'type'      => 'text',
            'escape'    => true
        ))->addColumn('message', array(
            'header'		=> Mage::helper('connector')->__('Send Message'),
            'align'		=> 'left',
            'width'		=> '300px',
            'index'     => 'message',
            'type'      => 'text',
            'escape'    => true
        ))->addColumn('event_name', array(
            'header'        => Mage::helper('connector')->__('Email Name'),
            'align'         => 'left',
            'index'         => 'event_name',
            'width'		    => '100px',
            'type'          => 'string',
            'escape'        => true,
        ))->addColumn('created_at', array(
            'header'    => Mage::helper('connector')->__('Created At'),
            'align'     => 'center',
            'width'     => '100px',
            'index'     => 'created_at',
            'type'      => 'datetime',
            'escape'    => true
        ))->addColumn('updated_at', array(
            'header'    => Mage::helper('connector')->__('Updated At'),
            'align'     => 'center',
            'width'     => '100px',
            'index'     => 'updated_at',
            'type'      => 'datetime',
            'escape'    => true
        ))->addColumn('sent_at', array(
            'header'    => Mage::helper('connector')->__('Sent At'),
            'align'     => 'center',
            'width'     => '100px',
            'index'     => 'sent_at',
            'type'     => 'datetime',
            'escape'   => true
        ));
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('website_id', array(
                'header'    => Mage::helper('customer')->__('Website'),
                'align'     => 'center',
                'width'     => '80px',
                'type'      => 'options',
                'options'   => Mage::getSingleton('adminhtml/system_store')->getWebsiteOptionHash(true),
                'index'     => 'website_id',
            ));
        }

        $this->addExportType('*/*/exportCsv', Mage::helper('connector')->__('CSV'));
        return parent::_prepareColumns();
    }

    /**
	 * Get the store selected.
	 * @return Mage_Core_Model_Store
	 * @throws Exception
	 */
    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }


    /**
	 * @return $this|Mage_Adminhtml_Block_Widget_Grid
	 */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('campaign');
        $this->getMassactionBlock()->addItem('delete', array (
		        'label'=> Mage::helper('connector')->__('Delete'),
                'url'  => $this->getUrl('*/*/massDelete'),
                'confirm'  => Mage::helper('connector')->__('Are you sure?')
	        )
        );

        $this->getMassactionBlock()->addItem('resend', array('label'=>Mage::helper('connector')->__('Resend'),'url'=>$this->getUrl('*/*/massResend')));
        $this->getMassactionBlock()->addItem('re-create', array('label'=>Mage::helper('connector')->__('Recreate'),'url'=>$this->getUrl('*/*/massRecreate')));
        return $this;
    }

    /**
	 * Grid selected url.
	 * @return string
	 */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
    /**
	 * Custom callback action for the campaign.
     *
	 * @param $collection
	 * @param $column
	 */
    public function filterCallbackContact($collection, $column)
	{
		$field = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();
		$value = $column->getFilter()->getValue();

        if ($value == 'null') {
	        $collection->addFieldToFilter($field, array('null' => true) );
        } else {
	        $collection->addFieldToFilter($field, array('notnull' => true));
        }
	}

}