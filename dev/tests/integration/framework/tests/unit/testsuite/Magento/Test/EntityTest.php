<?php
/**
 * {license_notice}
 *
 * @category    Magento
 * @package     Magento
 * @subpackage  integration_tests
 * @copyright   {copyright}
 * @license     {license_link}
 */

class Magento_Test_EntityTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Mage_Core_Model_Abstract|PHPUnit_Framework_MockObject_MockObject
     */
    protected $_model;

    /**
     * Callback for save method in mocked model
     */
    public function saveModelSuccessfully()
    {
        $this->_model->setId('1');
    }

    /**
     * Callback for save method in mocked model
     */
    public function saveModelAndFailOnUpdate()
    {
        if (!$this->_model->getId()) {
            $this->saveModelSuccessfully();
        } else {
            throw new Exception('Synthetic model update failure.');
        }
    }

    /**
     * Callback for delete method in mocked model
     */
    public function deleteModelSuccessfully()
    {
        $this->_model->setId(null);
    }

    public function crudDataProvider()
    {
        return array(
            'successful CRUD'         => array('saveModelSuccessfully'),
            'cleanup on update error' => array('saveModelAndFailOnUpdate', 'Exception'),
        );
    }

    /**
     * @dataProvider crudDataProvider
     */
    public function testTestCrud($saveCallback, $expectedException = null)
    {
        $this->setExpectedException($expectedException);

        $this->_model = $this->getMock(
            'Mage_Core_Model_Abstract',
            array('load', 'save', 'delete', 'getIdFieldName')
        );

        $this->_model->expects($this->atLeastOnce())
            ->method('load');
        $this->_model->expects($this->atLeastOnce())
            ->method('save')
            ->will($this->returnCallback(array($this, $saveCallback)));
        /* It's important that 'delete' should be always called to guarantee the cleanup */
        $this->_model->expects($this->atLeastOnce())
            ->method('delete')
            ->will($this->returnCallback(array($this, 'deleteModelSuccessfully')));

        $this->_model->expects($this->any())
            ->method('getIdFieldName')
            ->will($this->returnValue('id'));

        $test = $this->getMock(
            'Magento_Test_Entity',
            array('_getEmptyModel'),
            array($this->_model, array('test' => 'test'))
        );

        $test->expects($this->any())
            ->method('_getEmptyModel')
            ->will($this->returnValue($this->_model));
        $test->testCrud();

    }
}
