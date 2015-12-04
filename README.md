# Feature Branch Plugin

Composer plugin to allow easy-peasy management of feature branches across multiple repositories

## Usage

Update your composer.json file to include the repositories you'd like to watch. Then any subsequent `composer update` or `composer install` commands will use matching features branches (or fall back to the specified dependency in composer.json)

`
	"extra": {
      "feature-branch-repositories": [
        "composer/composer"
      ]
    }
	





