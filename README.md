## Payabbhi Plugin for Magento v2.x.x

This extension is built on [Payabbhi PHP Library](https://github.com/payabbhi/payabbhi-php) to provide seamless integration of [Payabbhi Checkout](https://payabbhi.com/docs/checkout) with Magento 2.x.x


### Installation via Composer


Run the following commands from Magento installation root directory.

```
composer require payabbhi/magento2
bin/magento module:enable Payabbhi_Magento
```
On running `bin/magento module:status`, you should be able to see `Payabbhi_Magento` in the module list


### Configuration

Make sure you have signed up for your [Payabbhi Account](https://payabbhi.com/docs/account) and downloaded the [API keys](https://payabbhi.com/docs/account/#api-keys) from the [Portal](https://payabbhi.com/portal).

1. Navigate to `Magento Dashboard` -> `Stores` -> `Configuration` -> `Payment Methods`.
2. Click on `Payabbhi (Card / NetBanking / Wallet)` to configure `Payabbhi`:
  - [Access ID](https://payabbhi.com/docs/account/#api-keys)
  - [Secret Key](https://payabbhi.com/docs/account/#api-keys)
  - [payment_auto_capture](https://payabbhi.com/docs/api/#create-an-order)
  - `Enabled` - Change to `Yes`
3. Save the settings.

If you do not see Payabbhi in your gateway list, please clear your Magento Cache from your admin
panel (`System` -> `Cache Management`).

[Payabbhi Checkout](https://payabbhi.com/docs/checkout) is now enabled in Magento.
