<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 26/12/18
 * Time: 11:55 AM
 */

namespace Saulmoralespa\PagoFacilChile\Controller\Payment;


use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Payment\Transaction;

class Notify extends \Magento\Framework\App\Action\Action
{

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
     * @var \Saulmoralespa\PagoFacilChile\Logger\Logger
     */
    protected $_pstPagoFacilLogger;

    /**
     * @var \Saulmoralespa\PagoFacilChile\Model\Factory\Connector
     */
    protected $_tpConnector;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;


    /**
     * Notify constructor.
     * @param \Saulmoralespa\PagoFacilChile\Logger\Logger $pstPagoFacilLogger
     * @param \Saulmoralespa\PagoFacilChile\Model\Factory\Connector $tpc
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Psr\Log\LoggerInterface $logger
     * @param PaymentHelper $paymentHelper
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param Transaction\BuilderInterface $transactionBuilder
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Saulmoralespa\PagoFacilChile\Logger\Logger $pstPagoFacilLogger,
        \Saulmoralespa\PagoFacilChile\Model\Factory\Connector $tpc,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        PaymentHelper $paymentHelper,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
    )
    {
        parent::__construct($context);

        $this->_scopeConfig = $scopeConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_logger = $logger;
        $this->_paymentHelper = $paymentHelper;
        $this->_transactionRepository = $transactionRepository;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_pstPagoFacilLogger = $pstPagoFacilLogger;
        $this->_tpConnector = $tpc;
        $this->request = $request;
        $this->formKey = $formKey;
        $this->request->setParam('form_key', $this->formKey->getFormKey());
        $this->orderSender = $orderSender;
    }

    public function execute()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        if (empty($params))
            exit;

        $this->_pstPagoFacilLogger->debug(print_r($params, true));

        $reference = $request->getParam('x_reference');
        $reference = explode('_', $reference);
        $order_id = $reference[0];

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order_model = $objectManager->get('Magento\Sales\Model\Order');
        $order = $order_model->load($order_id);

        $method = $order->getPayment()->getMethod();
        $methodInstance = $this->_paymentHelper->getMethodInstance($method);
        $totalOrder = $methodInstance->getAmount($order);
        $ct_monto = $request->getParam('x_amount');

        if ($ct_monto != $totalOrder)
            exit;

        $status = $request->getParam('x_result');

        $payment = $order->getPayment();

        $statuses = $methodInstance->getOrderStates();


        if ($status == 'pending')
            exit;


        switch ($status){
            case 'completed':
                $payment->setIsTransactionPending(false);
                $payment->setIsTransactionApproved(true);
                $status = $statuses["approved"];
                $state = \Magento\Sales\Model\Order::STATE_PROCESSING;

                $message = __('Payment approved');

                break;
            case 'failed':
                $payment->setIsTransactionDenied(true);
                $status = $statuses["rejected"];
                $state = \Magento\Sales\Model\Order::STATE_CANCELED;

                $order->cancel();

                $message = __('Payment declined');
        }

        $order->setState($state)->setStatus($status);
        if(!$order->getEmailSent()){
            $this->orderSender->send($order, true);
        }
        $payment->setSkipOrderProcessing(true);

        $transaction = $this->_transactionBuilder->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($payment->getTransactionId())
            ->build(Transaction::TYPE_ORDER);

        $payment->addTransactionCommentsToOrder($transaction, $message);

        $transaction->save();

        $order->save();

    }
}
