<?php
/**
 * Custom payment method in Magento 2
 * @category    GooglePay
 * @package     Apexx_Googlepay
 */
namespace Apexx\Googlepay\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Apexx\Googlepay\Helper\Data as GooglePayHelper;

/**
 * Class RefundHandler
 * @package Apexx\Googlepay\Gateway\Response
 */
class RefundHandler implements HandlerInterface
{
    const TXN_ID = '_id';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var GooglePayHelper
     */
    protected  $googlepayHelper;

    /**
     * RefundHandler constructor.
     * @param SubjectReader $subjectReader
     * @param GooglePayHelper $googlepayHelper
     */
    public function __construct(
        SubjectReader $subjectReader,
        GooglePayHelper $googlepayHelper
    )
    {
        $this->subjectReader = $subjectReader;
        $this->googlepayHelper = $googlepayHelper;
    }

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($response) || !is_array($response)) {
            throw new ClientException(__('Response does not exist'));
        }

        if ($response['status'] == 'FAILED') {
            if ($response['errors']) {
                if (isset($response['errors'][0]['error_message'])) {
                    throw new ClientException(__($response['errors'][0]['error_message']));
                } else {
                    if (isset($response['reason_message'])) {
                        throw new ClientException(__($response['reason_message']));
                    }
                }
            }
            throw new ClientException(__('A server error stopped your order from being placed.'));
        }

        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new ClientException(__('Payment data object should be provided'));
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment->setTransactionId($response[self::TXN_ID]);
        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);
        $payment->setTransactionAdditionalInfo('raw_details_info',$response);
    }
}
