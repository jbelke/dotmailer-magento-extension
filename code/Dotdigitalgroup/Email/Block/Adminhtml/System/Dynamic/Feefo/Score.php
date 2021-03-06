<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Feefo_Score extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        //passcode to append for url
        $passcode = Mage::helper('connector')->getPasscode();

        if(!strlen($passcode))
            $passcode = '[PLEASE SET UP A PASSCODE]';

        //generate the base url and display for default store id
        $baseUrl = Mage::helper('connector')->generateDynamicUrl();

        //display the full url
        $text = sprintf('%sconnector/feefo/score/code/%s', $baseUrl, $passcode);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}