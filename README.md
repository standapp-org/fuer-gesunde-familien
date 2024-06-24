# Für gesunde Familien

## Prerequisites

We must have docker and ddev installed on our machine.

Installation guide for ddev can be found here:
https://ddev.readthedocs.io/en/latest/users/install/ddev-installation/#__tabbed_1_1

## Installation

```
git clone git@github.com:standapp-org/fuer-gesunde-familien.git
cd fuer-gesunde-familien
ddev start

# install the dependencies
ddev composer install

# load the ssh session into the container
ddev auth ssh

# import the database + files
ddev app:db
ddev app:files

# launch the website
ddev launch
```

# Getting started

```shell
ddev composer install
```

# Code formattiong

* npm install
* configure phpstorm so that prettier is used for formatting on save, in prettier
    * Add "php,html" in setting "run for files". It should look like this: `{**/*,*}.{js,ts,jsx,tsx,vue,astro,php,html}`
* tip: optimize imports with phpstorm with shortcut `ctrl + alt + o`
