<?php class Cammino_Messenger_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * Method that checks if there is a configured message for given config field
     * @param string $fieldName Config field of needle message
     * @param integer $orderId Default is null. If not null and there was no message found on configuration for the $fieldName passed, it's assumed to be a status change that will send the configured default message.
     * @return string
     */
    private static function getMessage($fieldName, $orderId = null) {
        $message = Mage::getStoreConfig('messenger/messages/' . $fieldName);
        if ($orderId) {
            $message = self::replaceVars($message ? $message : Mage::getStoreConfig('messenger/messages/message_order_status_default'), $orderId);
        }
        return $message;
    }

    /**
     * Replace message variables with order information
     * @param string $message Message to be formatted
     * @param integer $orderId Entity ID of the order that is being altered
     * @return string Formatted message
     */
    private static function replaceVars($message, $orderId) {
        $order = Mage::getModel('sales/order')->load($orderId);
        $vars = array(
            'cliente' => Mage::getModel('customer/customer')->load($order->getCustomerId())->getName(),
            'pedido' => $order->getIncrementId(),
            'status' => $order->getStatusLabel()
        );
        foreach($vars as $key=>$value) {
            $message = str_replace('(' . $key . ')', $value, $message);
        }
        return $message;
    }

    /**
     * Method that selects and construct the configured API communication class
     * @return instance of API class
     */
    private static function getApiClass() {
        $className = Mage::getStoreConfig('messenger/api_config/used_api');
        if (empty($className)) {
            return null;
        }
        return Mage::getModel('messenger/' . $className);
    }

    /**
     * Method called when a customer registration in successful. It gets the API and message and calls the send method from the configured API class.
     */
    public function sendGreetCustomer($observer)
    {
        if ($message = $this->getMessage('message_signup') || $api = $this->getApiClass()) {
            return;
        }
        $customer = $observer->getEvent()->getCustomer();
        $api->sendMessage($message, $customer->getPrimaryBillingAddress()->getTelephone());
    }

    /**
     * Method called when order history is saved. It checks wich history is the new one, and if it is the first time an order is entering that status. If the conditions are met, it then get's the API and message and calls the send method of the API class.
     */
    public function sendOrderStatusChange($observer) {
        $history = $observer->getEvent()->getStatusHistory()->getData();
        if ((empty(Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll('select * from sales_flat_order_status_history where parent_id = ' . $history['parent_id'] .' and entity_id > "' . $history['entity_id'] . '";'))) && ($history['created_at'] == $history['updated_at']) && (count(Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll('select * from sales_flat_order_status_history where parent_id = ' . $history['parent_id'] .' and status = "' . $history['status'] . '";')) == 1)) {
            $order = Mage::getModel('sales/order')->load($history['parent_id']);
            $message = $this->getMessage('message_order_status_' . $order->getStatus(), $history['parent_id']);
            if ($message && $api = $this->getApiClass()) {
                $api->sendMessage($message, $order->getShippingAddress()->getTelephone());
            }
        }
    }
}