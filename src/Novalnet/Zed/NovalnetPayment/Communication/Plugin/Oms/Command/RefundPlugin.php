<?php

namespace Novalnet\Zed\NovalnetPayment\Communication\Plugin\Oms\Command;

use Generated\Shared\Transfer\NovalnetRefundTransfer;
use Orm\Zed\Sales\Persistence\SpySalesOrder;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\Oms\Business\Util\ReadOnlyArrayObject;
use Spryker\Zed\Oms\Dependency\Plugin\Command\CommandByOrderInterface;

/**
 * @method \Novalnet\Zed\NovalnetPayment\Business\NovalnetPaymentFacadeInterface getFacade()
 * @method \Novalnet\Zed\NovalnetPayment\Communication\NovalnetPaymentCommunicationFactory getFactory()
 * @method \Novalnet\Zed\NovalnetPayment\NovalnetPaymentConfig getConfig()
 * @method \Novalnet\Zed\NovalnetPayment\Persistence\NovalnetPaymentQueryContainerInterface getQueryContainer()
 */
class RefundPlugin extends AbstractPlugin implements CommandByOrderInterface
{
    /**
     * @api
     *
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem[] $salesOrderItems
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param \Spryker\Zed\Oms\Business\Util\ReadOnlyArrayObject $data
     *
     * @return void
     */
    public function run(array $salesOrderItems, SpySalesOrder $orderEntity, ReadOnlyArrayObject $data)
    {
        $refundTransfer = $this->getRefundTransfer($salesOrderItems, $orderEntity);
        $orderTransfer = $this->getOrderTransfer($orderEntity);

        $merchantDetails = [];
        foreach ($refundTransfer->getExpenses() as $itemExpenses) {
            if (isset($merchantDetails[$itemExpenses->getMerchantReference()])) {
                $merchantDetails[$itemExpenses->getMerchantReference()] += $itemExpenses->getSumPrice();
            } else {
                $merchantDetails[$itemExpenses->getMerchantReference()] = $itemExpenses->getSumPrice();
            }
        }

        foreach ($refundTransfer->getItems() as $itemTransfer) {
            if (isset($merchantDetails[$itemTransfer->getMerchantReference()])) {
                $merchantDetails[$itemTransfer->getMerchantReference()] += $itemTransfer->getSumPriceToPayAggregation();
            } else {
                $merchantDetails[$itemTransfer->getMerchantReference()] = $itemTransfer->getSumPriceToPayAggregation();
            }
        }

        $config = $this->getConfig()->getRequestStandardParameter()->getSubVendors();
        $refundDetails = [];
        foreach ($merchantDetails as $merchantReference => $totalAmount) {
            if (isset($config[$merchantReference])) {
                $refundDetails[$merchantReference] = $totalAmount;
            } else {
                if (isset($refundDetails['amount'])) {
                    $refundDetails['amount'] += $totalAmount;
                } else {
                    $refundDetails['amount'] = $totalAmount;
                }
            }
        }

        $novalnetRefundTransfer = new NovalnetRefundTransfer();
        $novalnetRefundTransfer->setMerchantReference($refundDetails);
        $novalnetRefundTransfer->setAmount($refundTransfer->getAmount());
        $novalnetRefundTransfer->setOrder($this->getOrderTransfer($orderEntity));

        $paymentRefundResult = $this->getFacade()->refundPayment($novalnetRefundTransfer);

        if ($paymentRefundResult->transaction->status == 'CONFIRMED') {
            $refundTransfer->setComment($paymentRefundResult->result->status_text);
            $this->getFactory()
                ->getRefundFacade()
                ->saveRefund($refundTransfer);
        }

        return [];
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     *
     * @return \Generated\Shared\Transfer\OrderTransfer
     */
    protected function getOrderTransfer(SpySalesOrder $orderEntity)
    {
        return $this
            ->getFactory()
            ->getSalesFacade()
            ->getOrderByIdSalesOrder($orderEntity->getIdSalesOrder());
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem[] $salesOrderItems
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     *
     * @return \Generated\Shared\Transfer\RefundTransfer
     */
    protected function getRefundTransfer(array $salesOrderItems, SpySalesOrder $orderEntity)
    {
        return $this
            ->getFactory()
            ->getRefundFacade()
            ->calculateRefund($salesOrderItems, $orderEntity);
    }
}
