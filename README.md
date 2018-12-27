PST Pago Fácil SpA  Magento 2
============================================================

## Installation

Use composer package manager

```bash
saulmoralespa/magento2-pago-facil-chile
```

Execute the commands

```bash
php bin/magento module:enable Saulmoralespa_PagoFacilChile --clear-static-content
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```