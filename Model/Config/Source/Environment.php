<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 25/12/18
 * Time: 10:30 AM
 */

namespace Saulmoralespa\PagoFacilChile\Model\Config\Source;


class Environment
{
    public function toOptionArray()
    {
        return [
            ['value' => 'test', 'label' => __('Development')],
            ['value' => 'prod', 'label' => __('Production')]
        ];
    }
}