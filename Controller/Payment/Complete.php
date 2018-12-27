<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 27/12/18
 * Time: 04:24 AM
 */

namespace Saulmoralespa\PagoFacilChile\Controller\Payment;


class Complete extends \Magento\Framework\App\Action\Action
{
    public function execute()
    {
        $request = $this->getRequest();

        $status = $request->getParam('x_result');

        //if order status  processing

        if ($status == 'completed')
            $this->_redirect('checkout/onepage/success');
    }

}