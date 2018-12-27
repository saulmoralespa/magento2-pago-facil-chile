<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 26/12/18
 * Time: 01:14 AM
 */

namespace Saulmoralespa\PagoFacilChile\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

class CustomConfigProvider implements ConfigProviderInterface
{

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;

    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepo
    )
    {
        $this->_assetRepo = $assetRepo;
    }

    public function getConfig()
    {
        $data = [
            'logoUrl' => $this->_assetRepo->getUrl("Saulmoralespa_PagoFacilChile::images/logo.png")
        ];

        return $data;
    }
}