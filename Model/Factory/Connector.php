<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 25/12/18
 * Time: 08:03 PM
 */

namespace Saulmoralespa\PagoFacilChile\Model\Factory;


class Connector
{
    protected $_scopeConfig;

    protected $_enviroment;

    protected $_token_user;

    protected $_sandbox = false;

    protected $_account_id;

    /**
     * Connector constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->_scopeConfig = $scopeConfig;

        $this->_enviroment = $this->_scopeConfig->getValue('payment/pagofacilchile/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ($this->_enviroment == 'test'){
            $this->_sandbox = true;
            $this->_token_user = $this->_scopeConfig->getValue('payment/pagofacilchile/enviroment_g/development/user_token',   \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $this->_account_id = $this->_scopeConfig->getValue('payment/pagofacilchile/enviroment_g/development/account_id',   \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }else{
            $this->_token_user = $this->_scopeConfig->getValue('payment/pagofacilchile/enviroment_g/production/user_token',   \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $this->_account_id = $this->_scopeConfig->getValue('payment/pagofacilchile/enviroment_g/production/account_id',   \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
    }

    /**
     * @return mixed
     */
    public function getEnviroment()
    {
        return $this->_sandbox;
    }

    /**
     * @return mixed
     */
    public function getUserToken()
    {
        return $this->_token_user;
    }

    /**
     * @return mixed
     */
    public function accountId()
    {
        return $this->_account_id;
    }
}