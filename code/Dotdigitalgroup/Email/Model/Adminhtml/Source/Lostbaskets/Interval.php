<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Lostbaskets_Interval
{

    /**
     * available times
     * @var array
     */
    protected $_times = array(1,2,3,4,5,6,12,24,36,48,60,72,84,96,108,120, 240);


    /**
     * send to campain options hours
     * @return array
     */
    public function toOptionArray()
    {
        $result = $row = array();
        $i = 0;
        foreach ($this->_times as $one) {

            if ($i == 0) {
	            $row = array(
		            'value' => $one,
		            'label' => Mage::helper( 'connector' )->__( $one . ' Hour' )
	            );
            } else {
	            $row = array(
		            'value' => $one,
		            'label' => Mage::helper( 'connector' )->__( $one . ' Hours' )
	            );
            }
            $result[] = $row;
	        $i++;
        }

        return $result;
    }
}
