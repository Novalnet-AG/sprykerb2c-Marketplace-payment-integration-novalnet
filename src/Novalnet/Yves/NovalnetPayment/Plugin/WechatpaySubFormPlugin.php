<?php

namespace Novalnet\Yves\NovalnetPayment\Plugin;

use Spryker\Yves\Kernel\AbstractPlugin;
use Spryker\Yves\StepEngine\Dependency\Plugin\Form\SubFormPluginInterface;

/**
 * @method \Novalnet\Yves\NovalnetPayment\NovalnetPaymentFactory getFactory()
 */
class WechatpaySubFormPlugin extends AbstractPlugin implements SubFormPluginInterface
{
    /**
     * @return \Novalnet\Yves\NovalnetPayment\Form\WechatpaySubForm
     */
    public function createSubForm()
    {
        return $this->getFactory()->createWechatpayForm();
    }

    /**
     * @return \Spryker\Yves\StepEngine\Dependency\Form\StepEngineFormDataProviderInterface
     */
    public function createSubFormDataProvider()
    {
        return $this->getFactory()->createWechatpayFormDataProvider();
    }
}
