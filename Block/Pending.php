<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 28/12/18
 * Time: 04:08 PM
 */

namespace Saulmoralespa\PagoFacilChile\Block;

class Pending extends \Magento\Framework\View\Element\Template
{
    public function __construct(\Magento\Framework\View\Element\Template\Context $context)
    {
        parent::__construct($context);
    }

    public function getMessage()
    {
        return __('The status of the order is pending, waiting to process the payment by  Pago Fácil');
    }

    public function getUrlHome()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
}