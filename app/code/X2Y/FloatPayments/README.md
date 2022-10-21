# Float payments

## Technical feature

### Module configuration

1. Package details [composer.json](composer.json).
2. Module configuration details (sequence) in [module.xml](etc/module.xml).
3. Module configuration available through Stores->Configuration->Sales->Payment Methods [system.xml](etc/adminhtml/system.xml)
4. Module enable/disable logic here: `\X2Y\FloatPayments\Model\Ui\ConfigProvider::isActive`, to enable you should: turn on method and add credentials.
5. Activates module on checkout: `\X2Y\FloatPayments\Observer\CheckAvailability::execute`, to see method on checkout cart total should less maxAmount and more than minAmount.
Check `payment/float/api/login_data` config in `core_config_data` table.
6. Login merchant into Float system and get additional data: `\X2Y\FloatPayments\Model\Spi\LoginInterface`
this SPI interface makes request to Float, receive login details and save it into
`payment/float/api/login_data` and `payment/float/api/token`, no need to refresh token, it has no live time.
But In case it becomes non-valid you can remove it from db so **LoginInterface** can refresh data.

### Gateway configuration

Check [config.xml](etc/config.xml) for default configurations.

### Gateway Facade configurations

Check [di.xml](etc/di.xml) for details.

---

**Float Payments Gateway Facade:**

```xml
<virtualType name="FloatPaymentsGatewayFacade" type="Magento\Payment\Model\Method\Adapter">
    <arguments>
        <argument name="code" xsi:type="const">\X2Y\FloatPayments\Helper\Data::METHOD_CODE</argument>
        <argument name="formBlockType" xsi:type="string">Magento\Payment\Block\Form</argument>
        <argument name="infoBlockType" xsi:type="string">Magento\Payment\Block\Info</argument>
        <argument name="valueHandlerPool" xsi:type="object">FloatPaymentsGatewayValueHandlerPool</argument>
        <argument name="commandPool" xsi:type="object">FloatPaymentsGatewayCommandPool</argument>
        <argument name="validatorPool" xsi:type="object">FloatValidatorPool</argument>
    </arguments>
</virtualType>
```

---

**Sale command:**

Implemented one **sale command**, as Float order should move to **processing** stage after success response.

This command is only for saving transaction information.

```xml
<!-- Commands Pool -->
    <virtualType name="FloatPaymentsGatewayCommandPool" type="Magento\Payment\Gateway\Command\CommandPool">
        <arguments>
            <argument name="commands" xsi:type="array">
                <item name="sale" xsi:type="string">X2Y\FloatPayments\Gateway\SaleCommand</item>
            </argument>
        </arguments>
    </virtualType>
```

---

**Validators:**

Implemented **country** and **currency** validators to enable/disable payment method on checkout for different countries and currencies:

```xml
<!-- Validator Pool -->
    <virtualType name="FloatValidatorPool" type="Magento\Payment\Gateway\Validator\ValidatorPool">
        <arguments>
            <argument name="validators" xsi:type="array">
                <item name="currency" xsi:type="string">X2Y\FloatPayments\Gateway\Validator\Currency</item>
                <item name="country" xsi:type="string">X2Y\FloatPayments\Gateway\Validator\Country</item>
            </argument>
        </arguments>
    </virtualType>
```

---

**Logger**

Logger has debug and error handlers.

Please use

`\X2Y\FloatPayments\Helper\Data::logDebug` and
`\X2Y\FloatPayments\Helper\Data::logError` methods for logging

Merchant can disable logging in admin.

```xml
<!-- Custom debug log  -->
    <virtualType name="DebugLoggerHandler" type="Magento\Framework\Logger\Handler\Base">
        <arguments>
            <argument name="fileName" xsi:type="string">/var/log/float.log</argument>
        </arguments>
    </virtualType>
    <virtualType name="ErrorLoggerHandler" type="Magento\Framework\Logger\Handler\System">
        <arguments>
            <argument name="fileName" xsi:type="string">/var/log/float.log</argument>
        </arguments>
    </virtualType>
    <virtualType name="floatLogger" type="Magento\Framework\Logger\Monolog">
        <arguments>
            <argument name="handlers" xsi:type="array">
                <item name="debug" xsi:type="object">DebugLoggerHandler</item>
                <item name="error" xsi:type="object">ErrorLoggerHandler</item>
            </argument>
        </arguments>
    </virtualType>
```


## Contributors
X2Y.io Core team

## License
[Open Source License](LICENSE.txt)
