# adscan

## Getting Started

### Requirements
- PHP 7.2

### Recommended Tools
- [PhpStorm](https://www.jetbrains.com/phpstorm/)

### Setting up a local environment

In the `environment` directory there is a `local-template` Windows batch file and shell script that can be copied and edited
to make it easier to set all of the required environment variables (after you have inserted values for
any missing variable or changed them to be correct for the environment you are working in).

- macOS
    - `source environment/<name-of-sh-file>.sh`
- Windows
    - `environment\<name-of-bat-file>.bat`

### macOS

#### Running from the command line
To run the adscan server locally, one may do something like
- `php -S localhost:5000` in the root directory of this repository and
  visit `http://localhost:5000/` in their browser.

#### Running from PhpStorm
- Open PhpStorm
- Select **Open** and navigate to the root of this repository on your local machine
- From the menu select **Run** then _Edit Configurations_
    - Add a new Configuration by pressing `+` and selecting **PHP Built-in Web Server**
        - Set the **Host** to _localhost_ (this is should already be set for you so just verify)
        - Set the **Port** to _63342_ (this is the default port for PhpStorm's built in Web Server)
        - Set the **Document root** to the root of this repository
        - Push **Apply** and **OK**
    - Now you can run the code using the Configuration that was just created
        - A console should open at the bottom of PhpStorm showing the invocation of php on localhost
    - Open a web browser and point it at localhost:63342

## Troubleshooting
If you are running locally and you experience an issue logging in
- check that you are connecting to the correct adscan database and using the correct username/password combination

If the database, username, and password are all correct and you see a blank page after logging in successfully
- check that you have updated all places where the database credentials are used; if you miss one, then you may end up
in an error state that is unexpected and be left viewing a blank page after logging in

## Testing ADScan

### Testing with Selenium in a Chrome browser

- Install the [Selenium Chrome Extension](https://chrome.google.com/webstore/detail/selenium-ide/mooikfkahbdckldjjndioackbalphokd?hl=en)
- Open `.side` files in `tests/selenium_scripts/chrome/` to run tests

### Testing with PyTest from the command-line

#### Prerequisites

WebDrivers must be downloaded and added to PATH for the browser you intend to test.

- Chrome can be found [here](https://sites.google.com/chromium.org/driver/)
- Firefox can be found [here](https://firefox-source-docs.mozilla.org/testing/geckodriver/Support.html)
- Edge can be found [here](https://developer.microsoft.com/en-us/microsoft-edge/tools/webdriver/#downloads)

#### Steps to run PyTests
- `pipenv install --dev`
- `pipenv shell`
- Edit and execute the `environment/local-testing` environment script
- `pytest`
