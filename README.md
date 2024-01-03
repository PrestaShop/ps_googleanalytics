![alt tag](views/img/ga_logo.png)

## About

Gain clear insights into important metrics about your customers, using Google Analytics 4.

To use it, you will need to create a Google Analytics account and insert your Google Analytics Identifier into the Module configuration page.

## Compatibility

PrestaShop: `1.7.7.0` or later

### Notes

Enhanced Ecommerce must be enabled in Google Analytics settings for full functionality. Otherwise, some data (refunds etc.) will not be visible. Follow [the related instructions][4].

### Configure

1. Install the module into your shop.
2. Create an account on Google Analytics if you do not have one.
3. Go on the "Configure" page of the module to insert your Google Analytics Identifier.
4. The data will then be sent to Google Analytics and you can monitor/explore it.

## Contributing

PrestaShop modules are open-source extensions to the PrestaShop e-commerce solution. Everyone is welcome and even encouraged to contribute with their own improvements.

Google Analytics is compatible with PrestaShop 1.7.7 and newer.

### Requirements

Contributors **must** follow the following rules:

* **Make your Pull Request on the "dev" branch**, NOT the "master" branch.
* Do NOT update the module's version number.
* Follow [the coding standards][1].

### Process in details

Contributors wishing to edit a module's files should follow the following process:

1. Create your GitHub account, if you do not have one already.
2. Fork the ps_googleanalytics project to your GitHub account.
3. Clone your fork to your local machine in the ```/modules``` directory of your PrestaShop installation.
4. Create a branch in your local clone of the module for your changes.
5. Change the files in your branch. Be sure to follow [the coding standards][1]!
6. Push your changed branch to your fork in your GitHub account.
7. Create a pull request for your changes **on the _'dev'_ branch** of the module's project. Be sure to follow [the commit message norm][2] in your pull request. If you need help to make a pull request, read the [Github help page about creating pull requests][3].
8. Wait for the maintainer team either to include your change in the codebase, or to comment on possible improvements you should make to your code.

That's it: you have contributed to this open source project! Congratulations!

[1]: https://devdocs.prestashop.com/1.7/development/coding-standards/
[2]: https://devdocs.prestashop.com/1.7/contribute/contribution-guidelines/
[3]: https://help.github.com/articles/using-pull-requests
[4]: https://support.google.com/analytics/answer/6032539
