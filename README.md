\*CAUTION\* The e-comprocessing Gateway Module Plugin for Zen Cart is deprecated and will no longer receive updates.
=============================
E-Comprocessing Gateway Module for Zen Cart
======================================

[![Software License](https://img.shields.io/badge/license-GPL-green.svg?style=flat)](LICENSE)

This is a Payment Module for Zen Cart, that gives you the ability to process payments through E-Comprocessing's Payment Gateway - Genesis.

Requirements
------------

* Zen Cart 1.5.x(Tested up to 2.0.1)
* [GenesisPHP v2.0.2](https://github.com/GenesisGateway/genesis_php/releases/tag/2.0.2) - (Integrated in Module)

GenesisPHP Requirements
------------

* PHP version 5.5.9 or newer
* PHP Extensions:
    * [BCMath](https://php.net/bcmath)
    * [CURL](https://php.net/curl) (required, only if you use the curl network interface)
    * [Filter](https://php.net/filter)
    * [Hash](https://php.net/hash)
    * [XMLReader](https://php.net/xmlreader)
    * [XMLWriter](https://php.net/xmlwriter)

Installation (Manual)
------------

* Upload the contents of folder (excluding ```README.md``` and ```YOUR_ADMIN```) to the ```<root>``` folder of your Zen Cart installation
* Upload the contents of folder ```YOUR_ADMIN``` to your ```<admin>``` folder of your Zen Cart installation
* Log into ```Zen Cart Administration Area``` with your Administrator account
* Go to ```Modules``` -> ```Payment``` -> Locate ```E-Comprocessing Checkout``` Module and click ```Install```
* Click ```Edit```, enter your credentials and configure the plugin to your needs

Supported Transactions & Payment Methods
---------------------
* ```E-Comprocessing Checkout``` Payment Method
  * __Apple Pay__
  * __Argencard__
  * __Aura__
  * __Authorize__
  * __Authorize (3D-Secure)__
  * __Baloto__
  * __Bancomer__
  * __Bancontact__
  * __Banco de Occidente__
  * __Banco do Brasil__
  * __BitPay__
  * __Boleto__
  * __Bradesco__
  * __Cabal__
  * __CashU__
  * __Cencosud__
  * __Davivienda__
  * __Efecty__
  * __Elo__
  * __eps__
  * __eZeeWallet__
  * __Fashioncheque__
  * __Google Pay__
  * __iDeal__
  * __iDebit__
  * __InstaDebit__
  * __InitRecurringSale__
  * __InitRecurringSale (3D-Secure)__
  * __Intersolve__
  * __Itau__
  * __Klarna__
  * __Multibanco__
  * __MyBank__
  * __Naranja__
  * __Nativa__
  * __Neosurf__
  * __Neteller__
  * __Online Banking__
    * __Interac Combined Pay-in (CPI)__
    * __Bancontact (BTC)__
    * __BLIK (BLK)__
    * __SPEI (SE)__
    * __LatiPay (PID)__
  * __OXXO__
  * __P24__
  * __Pago Facil__
  * __PayPal__
  * __PaySafeCard__
  * __PayU__
  * __Pix__
  * __POLi__
  * __Post Finance__
  * __PSE__
  * __RapiPago__
  * __Redpagos__
  * __SafetyPay__
  * __Sale__
  * __Sale (3D-Secure)__
  * __Santander__
  * __Sepa Direct Debit__
  * __SOFORT__
  * __Tarjeta Shopping__
  * __TCS__
  * __Trustly__
  * __TrustPay__
  * __UPI__
  * __WebMoney__
  * __WebPay__
  * __WeChat__

_Note_: If you have trouble with your credentials or terminal configuration, get in touch with our [support] team

You're now ready to process payments through our gateway.

Development
------------
* Install dev packages
```shell
composer install
```
* Run PHP Code Sniffer
```shell
composer php-cs
```
* Run PHP Mess Detector
```shell
composer php-md
```

[support]: mailto:tech-support@e-comprocessing.net
