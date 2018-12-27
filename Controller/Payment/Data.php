<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 26/12/18
 * Time: 02:17 AM
 */

namespace Saulmoralespa\PagoFacilChile\Controller\Payment;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Payment\Transaction;
use PSTPagoFacil\PSTPagoFacil;

class Data extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var PaymentHelper
     */
    protected $_paymentHelper;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $_transactionRepository;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    protected $_transactionBuilder;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Saulmoralespa\PagoFacilChile\Logger\Logger
     */
    protected $_pstPagoFacilLogger;

    /**
     * @var \Saulmoralespa\PagoFacilChile\Model\Factory\Connector
     */
    protected $_tpConnector;

    public function __construct(
        \Saulmoralespa\PagoFacilChile\Logger\Logger $pstPagoFacilLogger,
        \Saulmoralespa\PagoFacilChile\Model\Factory\Connector $tpc,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        PaymentHelper $paymentHelper,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    )
    {
        parent::__construct($context);
        $this->_url = $context->getUrl();
        $this->_scopeConfig = $scopeConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_logger = $logger;
        $this->_paymentHelper = $paymentHelper;
        $this->_transactionRepository = $transactionRepository;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_pstPagoFacilLogger = $pstPagoFacilLogger;
        $this->_tpConnector = $tpc;
    }

    protected function _getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    public function execute()
    {
        $url = '';

        try{
            $order = $this->_getCheckoutSession()->getLastRealOrder();
            $json = $this->generateTransaction($order);

            if ($json){
                $url = $json->payUrl;

                $payment = $order->getPayment();
                $payment->setTransactionId($json->idTrx)
                    ->setIsTransactionClosed(0);

                $payment->setParentTransactionId($order->getId());
                $payment->setIsTransactionPending(true);
                $transaction = $this->_transactionBuilder->setPayment($payment)
                    ->setOrder($order)
                    ->setTransactionId($payment->getTransactionId())
                    ->build(Transaction::TYPE_ORDER);

                $payment->addTransactionCommentsToOrder($transaction, __('pending'));

                $method = $order->getPayment()->getMethod();
                $methodInstance = $this->_paymentHelper->getMethodInstance($method);

                $statuses = $methodInstance->getOrderStates();
                $status = $statuses["pending"];
                $state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
                $order->setState($state)->setStatus($status);
                $payment->setSkipOrderProcessing(true);
                $order->save();
            }

        }catch (\Exception $exception){
            $this->_pstPagoFacilLogger->debug($exception->getMessage());
        }

        $result = $this->_resultJsonFactory->create();
        return $result->setData([
            'url' => $url
        ]);
    }

    public function generateTransaction($order)
    {
        $billing = $order->getBillingAddress();
        $shipping = $order->getShippingAddress();
        $country = empty($shipping->getCountryId())  ? $billing->getCountryId() : $shipping->getCountryId();

        $data = '';

        try{
            $pagoFacil = new PSTPagoFacil($this->_tpConnector->getUserToken());
            $pagoFacil->sandbox_mode($this->_tpConnector->getEnviroment());

            $orderId = $order->getId();
            $reference = $orderId . "_" . time();

            $transaction = array(
                'x_url_callback' => $this->_url->getUrl('pagofacilchile/payment/notify'),
                'x_url_cancel' => $this->_url->getUrl('checkout/onepage/failure'),
                'x_url_complete' => $this->_url->getUrl('checkout/onepage/success'), //pagofacilchile/payment/complete
                'x_customer_email' => $order->getCustomerEmail(),
                'x_reference' => $reference,
                'x_account_id' => $this->_tpConnector->accountId(),
                'x_amount' => $this->getAmount($order),
                'x_currency' => $order->getOrderCurrencyCode(),
                'x_shop_country' => $country
            );

            $data = $pagoFacil->createPayTransaction($transaction);

        }catch (\Exception $exception){
            $this->_pstPagoFacilLogger->debug($exception->getMessage());
        }

        return $data;
    }

    public function getAmount($order)
    {
           $amount = $order->getGrandTotal();
        $currencyCode = $order->getOrderCurrencyCode();
        if ($currencyCode === 'USD')
            return number_format($amount, 2, ".", "");
        return round($amount);

    }
}