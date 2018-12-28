PST Pago Fácil SpA  Magento 2
============================================================

## Description ##
Means of payment of Chile, supports Webpay of Transbank, Khipu and Multicaja

## Table of Contents

* [Installation](#installation)
* [Configuration](#configuration)


## Installation ##

Use composer package manager

```bash
composer require saulmoralespa/magento2-pago-facil-chile
```

Execute the commands

```bash
php bin/magento module:enable Saulmoralespa_PagoFacilChile --clear-static-content
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

## Configuration ##

### 1. Enter the configuration menu of the payment method ###
![Enter the configuration menu of the payment method](https://4.bp.blogspot.com/-vPfP40YDaPE/XCZpZS32NaI/AAAAAAAACnA/DGA4AibYG6ETZxmv5gwm4bq3fXszxE_0ACLcBGAs/s1600/configurationmenuofthepaymentmethod.png)