<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 27/12/18
 * Time: 04:24 AM
 */

namespace Saulmoralespa\PagoFacilChile\Controller\Payment;

use Magento\Framework\Controller\ResultFactory;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;

class Complete extends \Magento\Framework\App\Action\Action
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
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $_orderRepository;

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
        \Magento\Sales\Model\OrderRepository $orderRepository,
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
        $this->_orderRepository = $orderRepository;
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

        $reference = $request->getParam('x_reference');
        $reference = explode('_', $reference);
        $order_id = $reference[0];

        /** @var Order $order */
        $order = $this->_orderRepository->get($order_id);

        $method = $order->getPayment()->getMethod();
        $methodInstance = $this->_paymentHelper->getMethodInstance($method);
        $totalOrder = $methodInstance->getAmount($order);
        $ct_monto = $request->getParam('x_amount');


        if ($ct_monto != $totalOrder){
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('checkout/onepage/failure');
            return $resultRedirect;
        }


        $payment = $order->getPayment();

        $statuses = $methodInstance->getOrderStates();


        $statusTransaction = $request->getParam('x_result');

        $pendingOrder = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
        $failedOrder = \Magento\Sales\Model\Order::STATE_CANCELED;
        $aprovvedOrder =  \Magento\Sales\Model\Order::STATE_PROCESSING;


        $transaction = $this->_transactionRepository->getByTransactionType(
            Transaction::TYPE_ORDER,
            $payment->getId()
        );


        $pathRedirect = 'checkout/onepage/success';

        if ($order->getState() == $pendingOrder && $statusTransaction == 'pending'){
            $pathRedirect = "pagofacilchile/payment/pending";
        }elseif ($order->getState() == $failedOrder && $statusTransaction == 'failed'){
            $pathRedirect = "checkout/onepage/failure";
        }elseif ($order->getState() == $pendingOrder && $statusTransaction == 'failed'){

            $payment->setIsTransactionDenied(true);
            $status = $statuses["rejected"];
            $state = $failedOrder;

            $order->setState($state)->setStatus($status);
            if(!$order->getEmailSent()){
                $this->orderSender->send($order, true);
            }
            $payment->setSkipOrderProcessing(true);

            $message = __('Payment declined');

            $payment->addTransactionCommentsToOrder($transaction, $message);

            $transaction->save();
            $order->cancel()->save();

            $pathRedirect = "checkout/onepage/failure";
        }elseif ($order->getState() == $pendingOrder && $statusTransaction == 'completed'){

            $payment->setIsTransactionPending(false);
            $payment->setIsTransactionApproved(true);
            $status = $statuses["approved"];
            $state = $aprovvedOrder;

            $order->setState($state)->setStatus($status);
            if(!$order->getEmailSent()){
                $this->orderSender->send($order, true);
            }
            $payment->setSkipOrderProcessing(true);

            $message = __('Payment approved');

            $payment->addTransactionCommentsToOrder($transaction, $message);

            $transaction->save();

            $order->save();

        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($pathRedirect);
        return $resultRedirect;
    }

}
