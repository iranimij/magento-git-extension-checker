# Iranimij_GitExtensionChecker

The **Iranimij_GitExtensionChecker** module is on top of the `Yireo_ExtensionChecker` module. It focuses on analyzing the dependencies of Magento 2 modules that have been modified in the current Git branch compared to the `main` branch. This helps ensure that only the relevant module dependencies are checked, streamlining your development workflow.

## Features

- Detects modified modules by comparing the current branch with the `main` branch.
- Checks the dependencies of only the changed modules.
- Integrates seamlessly with Magento 2 development processes.

## Installation

To install the module, use Composer and run the Magento setup commands:

```bash
composer require iranimij/magento-git-extension-checker
bin/magento setup:upgrade
```

### Requirements
* Magento 2.4.x or higher
* PHP 8.0 or higher

## Usage

```bash
bin/magento Iranimij_GitExtensionChecker:scan
```

## License
OSL-3.0
